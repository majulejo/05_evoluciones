<?php
/**
 * API Endpoint para constantes vitales
 * Métodos: GET, POST, PUT, DELETE
 */

require_once '../database.php';

// Validar método HTTP
$method = validateMethod(['GET', 'POST', 'PUT', 'DELETE']);

// Inicializar base de datos
$db = new Database();

// Obtener parámetros
$constanteId = $_GET['id'] ?? null;
$pacienteId = $_GET['paciente_id'] ?? null;
$fecha = $_GET['fecha'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($constanteId) {
                // Obtener una constante específica
                $constante = $db->getById('constantes_vitales', $constanteId);
                if ($constante) {
                    ApiResponse::json(ApiResponse::success($constante, "Constante vital encontrada"));
                } else {
                    ApiResponse::json(ApiResponse::error("Constante vital no encontrada", 404));
                }
            } elseif ($pacienteId) {
                // Obtener constantes de un paciente específico
                $whereClause = 'paciente_id = :paciente_id';
                $params = [':paciente_id' => $pacienteId];
                
                // Filtrar por fecha si se proporciona
                if ($fecha) {
                    $whereClause .= ' AND DATE(fecha_hora) = :fecha';
                    $params[':fecha'] = $fecha;
                }
                
                $constantes = $db->getAll('constantes_vitales', $whereClause, $params, 'fecha_hora DESC');
                
                // Enriquecer con datos del paciente
                if (!empty($constantes)) {
                    $paciente = $db->getById('pacientes', $pacienteId);
                    $result = [
                        'paciente' => $paciente,
                        'constantes' => $constantes,
                        'total' => count($constantes)
                    ];
                } else {
                    $result = [
                        'paciente' => $db->getById('pacientes', $pacienteId),
                        'constantes' => [],
                        'total' => 0
                    ];
                }
                
                ApiResponse::json(ApiResponse::success($result, "Constantes vitales recuperadas"));
            } else {
                // Obtener todas las constantes recientes
                $constantes = $db->getAll('constantes_vitales', '', [], 'fecha_hora DESC', '50');
                ApiResponse::json(ApiResponse::success($constantes, "Constantes vitales recuperadas"));
            }
            break;
            
        case 'POST':
            // Crear nueva constante vital
            $data = getJsonInput();
            
            // Validar campos requeridos
            validateRequired($data, ['paciente_id']);
            
            // Verificar que el paciente existe
            $paciente = $db->getById('pacientes', $data['paciente_id']);
            if (!$paciente) {
                ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
            }
            
            // Sanitizar y preparar datos
            $constanteData = $db->sanitize([
                'paciente_id' => $data['paciente_id'],
                'fecha_hora' => $data['fecha_hora'] ?? date('Y-m-d H:i:s'),
                'temperatura' => $data['temperatura'] ?? null,
                'presion_sistolica' => $data['presion_sistolica'] ?? null,
                'presion_diastolica' => $data['presion_diastolica'] ?? null,
                'frecuencia_cardiaca' => $data['frecuencia_cardiaca'] ?? null,
                'frecuencia_respiratoria' => $data['frecuencia_respiratoria'] ?? null,
                'saturacion_oxigeno' => $data['saturacion_oxigeno'] ?? null
            ]);
            
            // Validar rangos de valores
            $validationErrors = [];
            
            if ($constanteData['temperatura'] && ($constanteData['temperatura'] < 30 || $constanteData['temperatura'] > 45)) {
                $validationErrors[] = "Temperatura fuera de rango válido (30-45°C)";
            }
            
            if ($constanteData['presion_sistolica'] && ($constanteData['presion_sistolica'] < 50 || $constanteData['presion_sistolica'] > 300)) {
                $validationErrors[] = "Presión sistólica fuera de rango válido (50-300 mmHg)";
            }
            
            if ($constanteData['frecuencia_cardiaca'] && ($constanteData['frecuencia_cardiaca'] < 20 || $constanteData['frecuencia_cardiaca'] > 250)) {
                $validationErrors[] = "Frecuencia cardíaca fuera de rango válido (20-250 bpm)";
            }
            
            if ($constanteData['saturacion_oxigeno'] && ($constanteData['saturacion_oxigeno'] < 50 || $constanteData['saturacion_oxigeno'] > 100)) {
                $validationErrors[] = "Saturación de oxígeno fuera de rango válido (50-100%)";
            }
            
            if (!empty($validationErrors)) {
                ApiResponse::json(ApiResponse::error("Errores de validación", 400, ['errors' => $validationErrors]));
            }
            
            $newConstanteId = $db->insert('constantes_vitales', $constanteData);
            
            logAction('vital_signs_created', ['constante_id' => $newConstanteId, 'paciente_id' => $data['paciente_id']]);
            
            // Obtener la constante creada con datos del paciente
            $createdConstante = $db->getById('constantes_vitales', $newConstanteId);
            $result = [
                'id' => $newConstanteId,
                'constante' => $createdConstante,
                'paciente' => $paciente
            ];
            
            ApiResponse::json(ApiResponse::success($result, "Constantes vitales registradas exitosamente"));
            break;
            
        case 'PUT':
            // Actualizar constante vital existente
            if (!$constanteId) {
                ApiResponse::json(ApiResponse::error("ID de la constante requerido", 400));
            }
            
            $data = getJsonInput();
            
            // Verificar que la constante existe
            $existingConstante = $db->getById('constantes_vitales', $constanteId);
            if (!$existingConstante) {
                ApiResponse::json(ApiResponse::error("Constante vital no encontrada", 404));
            }
            
            // Preparar datos para actualizar
            $updateData = [];
            $allowedFields = ['fecha_hora', 'temperatura', 'presion_sistolica', 'presion_diastolica', 
                            'frecuencia_cardiaca', 'frecuencia_respiratoria', 'saturacion_oxigeno'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                ApiResponse::json(ApiResponse::error("No hay datos para actualizar", 400));
            }
            
            // Sanitizar datos
            $updateData = $db->sanitize($updateData);
            
            $db->update('constantes_vitales', $updateData, 'id = :id', [':id' => $constanteId]);
            
            logAction('vital_signs_updated', ['constante_id' => $constanteId, 'data' => $updateData]);
            
            // Obtener constante actualizada
            $updatedConstante = $db->getById('constantes_vitales', $constanteId);
            
            ApiResponse::json(ApiResponse::success($updatedConstante, "Constantes vitales actualizadas"));
            break;
            
        case 'DELETE':
            // Eliminar constante vital
            if (!$constanteId) {
                ApiResponse::json(ApiResponse::error("ID de la constante requerido", 400));
            }
            
            // Verificar que la constante existe
            $existingConstante = $db->getById('constantes_vitales', $constanteId);
            if (!$existingConstante) {
                ApiResponse::json(ApiResponse::error("Constante vital no encontrada", 404));
            }
            
            $db->delete('constantes_vitales', 'id = :id', [':id' => $constanteId]);
            
            logAction('vital_signs_deleted', ['constante_id' => $constanteId]);
            
            ApiResponse::json(ApiResponse::success(null, "Constante vital eliminada"));
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en API constantes: " . $e->getMessage());
    ApiResponse::json(ApiResponse::error("Error interno del servidor: " . $e->getMessage(), 500));
}
?>