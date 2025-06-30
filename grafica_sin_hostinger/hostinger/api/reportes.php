<?php
/**
 * API Endpoint para reportes y estadísticas
 * Métodos: GET
 */

require_once '../database.php';

// Validar método HTTP
$method = validateMethod(['GET']);

// Inicializar base de datos
$db = new Database();

// Obtener tipo de reporte
$tipoReporte = $_GET['tipo'] ?? 'general';
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
$pacienteId = $_GET['paciente_id'] ?? null;

try {
    switch ($tipoReporte) {
        case 'general':
            // Estadísticas generales del sistema
            $estadisticas = [
                'pacientes' => [
                    'total' => $db->executeQuery("SELECT COUNT(*) as count FROM pacientes")->fetch()['count'],
                    'activos' => $db->executeQuery("SELECT COUNT(*) as count FROM pacientes WHERE activo = 1")->fetch()['count'],
                    'ingresos_hoy' => $db->executeQuery("SELECT COUNT(*) as count FROM pacientes WHERE DATE(fecha_ingreso) = CURDATE()")->fetch()['count']
                ],
                'constantes_vitales' => [
                    'total' => $db->executeQuery("SELECT COUNT(*) as count FROM constantes_vitales")->fetch()['count'],
                    'hoy' => $db->executeQuery("SELECT COUNT(*) as count FROM constantes_vitales WHERE DATE(fecha_hora) = CURDATE()")->fetch()['count'],
                    'ultima_semana' => $db->executeQuery("SELECT COUNT(*) as count FROM constantes_vitales WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['count']
                ],
                'ocupacion_camas' => []
            ];
            
            // Obtener ocupación de camas
            $camas = $db->executeQuery("
                SELECT cama, nombre, fecha_ingreso 
                FROM pacientes 
                WHERE activo = 1 
                ORDER BY cama ASC
            ")->fetchAll();
            
            $estadisticas['ocupacion_camas'] = $camas;
            
            ApiResponse::json(ApiResponse::success($estadisticas, "Estadísticas generales"));
            break;
            
        case 'constantes_paciente':
            // Constantes vitales de un paciente específico
            if (!$pacienteId) {
                ApiResponse::json(ApiResponse::error("ID del paciente requerido", 400));
            }
            
            // Verificar que el paciente existe
            $paciente = $db->getById('pacientes', $pacienteId);
            if (!$paciente) {
                ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
            }
            
            // Obtener constantes en el rango de fechas
            $constantes = $db->executeQuery("
                SELECT * FROM constantes_vitales 
                WHERE paciente_id = :paciente_id 
                AND DATE(fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY fecha_hora ASC
            ", [
                ':paciente_id' => $pacienteId,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ])->fetchAll();
            
            // Calcular promedios y rangos
            $resumen = [
                'total_registros' => count($constantes),
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ],
                'promedios' => [],
                'rangos' => []
            ];
            
            if (!empty($constantes)) {
                $campos = ['temperatura', 'presion_sistolica', 'presion_diastolica', 
                          'frecuencia_cardiaca', 'frecuencia_respiratoria', 'saturacion_oxigeno'];
                
                foreach ($campos as $campo) {
                    $valores = array_filter(array_column($constantes, $campo), function($val) {
                        return $val !== null && $val !== '';
                    });
                    
                    if (!empty($valores)) {
                        $resumen['promedios'][$campo] = round(array_sum($valores) / count($valores), 2);
                        $resumen['rangos'][$campo] = [
                            'minimo' => min($valores),
                            'maximo' => max($valores)
                        ];
                    }
                }
            }
            
            $resultado = [
                'paciente' => $paciente,
                'constantes' => $constantes,
                'resumen' => $resumen
            ];
            
            ApiResponse::json(ApiResponse::success($resultado, "Reporte de constantes vitales"));
            break;
            
        case 'tendencias':
            // Tendencias de constantes vitales por día
            $tendencias = $db->executeQuery("
                SELECT 
                    DATE(fecha_hora) as fecha,
                    COUNT(*) as total_mediciones,
                    AVG(temperatura) as temp_promedio,
                    AVG(presion_sistolica) as sistolica_promedio,
                    AVG(presion_diastolica) as diastolica_promedio,
                    AVG(frecuencia_cardiaca) as fc_promedio,
                    AVG(saturacion_oxigeno) as sat_promedio
                FROM constantes_vitales 
                WHERE DATE(fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY DATE(fecha_hora)
                ORDER BY fecha ASC
            ", [
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ])->fetchAll();
            
            // Formatear números
            foreach ($tendencias as &$dia) {
                $dia['temp_promedio'] = $dia['temp_promedio'] ? round($dia['temp_promedio'], 1) : null;
                $dia['sistolica_promedio'] = $dia['sistolica_promedio'] ? round($dia['sistolica_promedio'], 0) : null;
                $dia['diastolica_promedio'] = $dia['diastolica_promedio'] ? round($dia['diastolica_promedio'], 0) : null;
                $dia['fc_promedio'] = $dia['fc_promedio'] ? round($dia['fc_promedio'], 0) : null;
                $dia['sat_promedio'] = $dia['sat_promedio'] ? round($dia['sat_promedio'], 0) : null;
            }
            
            ApiResponse::json(ApiResponse::success([
                'periodo' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
                'tendencias' => $tendencias
            ], "Tendencias de constantes vitales"));
            break;
            
        case 'alertas':
            // Detectar valores fuera de rangos normales
            $alertas = $db->executeQuery("
                SELECT 
                    cv.*,
                    p.nombre,
                    p.cama,
                    CASE 
                        WHEN cv.temperatura < 36.0 OR cv.temperatura > 38.0 THEN 'Temperatura anormal'
                        WHEN cv.presion_sistolica > 140 OR cv.presion_sistolica < 90 THEN 'Presión arterial anormal'
                        WHEN cv.frecuencia_cardiaca > 100 OR cv.frecuencia_cardiaca < 60 THEN 'Frecuencia cardíaca anormal'
                        WHEN cv.saturacion_oxigeno < 95 THEN 'Saturación de oxígeno baja'
                        ELSE 'Normal'
                    END as tipo_alerta
                FROM constantes_vitales cv
                JOIN pacientes p ON cv.paciente_id = p.id
                WHERE p.activo = 1
                AND DATE(cv.fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin
                AND (
                    cv.temperatura < 36.0 OR cv.temperatura > 38.0 OR
                    cv.presion_sistolica > 140 OR cv.presion_sistolica < 90 OR
                    cv.frecuencia_cardiaca > 100 OR cv.frecuencia_cardiaca < 60 OR
                    cv.saturacion_oxigeno < 95
                )
                ORDER BY cv.fecha_hora DESC
                LIMIT 50
            ", [
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ])->fetchAll();
            
            ApiResponse::json(ApiResponse::success([
                'periodo' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
                'alertas' => $alertas,
                'total_alertas' => count($alertas)
            ], "Alertas de valores anormales"));
            break;
            
        case 'actividad':
            // Actividad del sistema (logs recientes)
            $actividad = $db->executeQuery("
                SELECT 
                    DATE(fecha_ingreso) as fecha,
                    COUNT(*) as nuevos_ingresos,
                    GROUP_CONCAT(nombre SEPARATOR ', ') as pacientes
                FROM pacientes 
                WHERE DATE(fecha_ingreso) BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY DATE(fecha_ingreso)
                ORDER BY fecha DESC
            ", [
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ])->fetchAll();
            
            // Actividad de mediciones
            $mediciones = $db->executeQuery("
                SELECT 
                    DATE(fecha_hora) as fecha,
                    COUNT(*) as total_mediciones,
                    COUNT(DISTINCT paciente_id) as pacientes_monitoreados
                FROM constantes_vitales 
                WHERE DATE(fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY DATE(fecha_hora)
                ORDER BY fecha DESC
            ", [
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin' => $fechaFin
            ])->fetchAll();
            
            ApiResponse::json(ApiResponse::success([
                'periodo' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
                'ingresos' => $actividad,
                'mediciones' => $mediciones
            ], "Actividad del sistema"));
            break;
            
        default:
            ApiResponse::json(ApiResponse::error("Tipo de reporte no válido", 400, [
                'tipos_disponibles' => ['general', 'constantes_paciente', 'tendencias', 'alertas', 'actividad']
            ]));
    }
    
} catch (Exception $e) {
    error_log("Error en API reportes: " . $e->getMessage());
    ApiResponse::json(ApiResponse::error("Error interno del servidor: " . $e->getMessage(), 500));
}
?>