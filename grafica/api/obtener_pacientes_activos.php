<?php
// api/obtener_pacientes_activos.php - VERSIÓN CORREGIDA PARA TU ESTRUCTURA REAL

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ INCLUIR TU CONFIG.PHP
require_once '../config.php';

try {
    // ✅ USAR TU FUNCIÓN DE CONEXIÓN
    $pdo = obtenerConexionBD();
    
    // ✅ OBTENER TODOS LOS PACIENTES ACTIVOS (usando tu estructura real)
    $sql = "SELECT 
                p.id,
                p.box,
                p.numero_box,
                p.nombre_completo,
                p.edad,
                p.peso,
                p.numero_historia,
                p.numero_hoja,
                p.fecha_ingreso,
                p.fecha_alta,
                p.estado,
                p.fecha_creacion,
                -- Calcular días de estancia
                DATEDIFF(NOW(), p.fecha_ingreso) as dias_estancia
            FROM pacientes p 
            WHERE p.estado = 'activo' 
            AND (p.fecha_alta IS NULL OR p.fecha_alta = '0000-00-00 00:00:00')
            ORDER BY p.box ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ✅ PROCESAR DATOS PARA CADA PACIENTE
    $pacientesFormateados = [];
    
    foreach ($pacientes as $paciente) {
        // Convertir tipos
        $paciente['id'] = (int)$paciente['id'];
        $paciente['box'] = (int)$paciente['box'];
        $paciente['edad'] = (int)$paciente['edad'];
        $paciente['peso'] = $paciente['peso'] ? (float)$paciente['peso'] : null;
        $paciente['dias_estancia'] = (int)$paciente['dias_estancia'];
        
        // Añadir campo 'activo' para compatibilidad
        $paciente['activo'] = ($paciente['estado'] === 'activo');
        
        // Obtener última actividad de constantes vitales
        try {
            $stmtConstantes = $pdo->prepare("SELECT MAX(fecha_registro) as ultima_constante FROM constantes_vitales WHERE box = ?");
            $stmtConstantes->execute([$paciente['box']]);
            $ultimaConstante = $stmtConstantes->fetchColumn();
        } catch (PDOException $e) {
            $ultimaConstante = null;
        }
        
        // Obtener última actividad de oxigenación
        try {
            $stmtOxigenacion = $pdo->prepare("SELECT MAX(fecha_registro) as ultima_oxigenacion FROM datos_oxigenacion WHERE box = ?");
            $stmtOxigenacion->execute([$paciente['box']]);
            $ultimaOxigenacion = $stmtOxigenacion->fetchColumn();
        } catch (PDOException $e) {
            $ultimaOxigenacion = null;
        }
        
        // Calcular última actividad
        $ultimaActividad = null;
        if ($ultimaConstante && $ultimaOxigenacion) {
            $ultimaActividad = max($ultimaConstante, $ultimaOxigenacion);
        } elseif ($ultimaConstante) {
            $ultimaActividad = $ultimaConstante;
        } elseif ($ultimaOxigenacion) {
            $ultimaActividad = $ultimaOxigenacion;
        }
        
        $paciente['ultima_actividad'] = $ultimaActividad;
        
        // Determinar estado del paciente basado en actividad
        if ($ultimaActividad) {
            $tiempoUltimaActividad = time() - strtotime($ultimaActividad);
            if ($tiempoUltimaActividad < 3600) { // Menos de 1 hora
                $paciente['estado_actividad'] = 'activo';
            } elseif ($tiempoUltimaActividad < 14400) { // Menos de 4 horas
                $paciente['estado_actividad'] = 'moderado';
            } else {
                $paciente['estado_actividad'] = 'inactivo';
            }
        } else {
            $paciente['estado_actividad'] = 'sin_datos';
        }
        
        $pacientesFormateados[] = $paciente;
    }
    
    // ✅ DETERMINAR BOXES OCUPADOS Y LIBRES
    $boxesOcupados = array_column($pacientesFormateados, 'box');
    $todosLosBoxes = range(1, 10); // Ajusta según tus boxes reales
    $boxesLibres = array_diff($todosLosBoxes, $boxesOcupados);
    
    // ✅ OBTENER ESTADÍSTICAS ADICIONALES
    $sqlEstadisticas = "SELECT 
                            estado, 
                            COUNT(*) as cantidad 
                        FROM pacientes 
                        GROUP BY estado";
    $stmtEstadisticas = $pdo->prepare($sqlEstadisticas);
    $stmtEstadisticas->execute();
    $estadisticas = $stmtEstadisticas->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'pacientes' => $pacientesFormateados,
            'total_pacientes' => count($pacientesFormateados),
            'boxes_ocupados' => $boxesOcupados,
            'boxes_libres' => array_values($boxesLibres),
            'total_boxes' => count($todosLosBoxes),
            'estadisticas_bd' => $estadisticas
        ],
        'debug_info' => [
            'consulta_ejecutada' => $sql,
            'total_registros_encontrados' => count($pacientes),
            'estructura_detectada' => 'estado_enum'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Error DB en obtener_pacientes_activos.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_details' => $e->getCode(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en obtener_pacientes_activos.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>