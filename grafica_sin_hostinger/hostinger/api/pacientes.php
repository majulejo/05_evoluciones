<?php
/**
 * API Endpoint para gestión de pacientes
 * Métodos: GET, POST, PUT, DELETE
 */

require_once '../database.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Validar método HTTP
$method = validateMethod(['GET', 'POST', 'PUT', 'DELETE']);

// Inicializar base de datos
$db = new Database();

// Obtener ID del paciente si se proporciona
$patientId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($patientId) {
                // Obtener un paciente específico
                $patient = $db->getById('pacientes', $patientId);
                if ($patient) {
                    ApiResponse::json(ApiResponse::success($patient, "Paciente encontrado"));
                } else {
                    ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
                }
            } else {
                // Obtener todos los pacientes activos
                $patients = $db->getAll('pacientes', 'activo = 1', [], 'fecha_ingreso DESC');
                ApiResponse::json(ApiResponse::success($patients, "Pacientes recuperados exitosamente"));
            }
            break;
            
        case 'POST':
            // Crear nuevo paciente
            $data = getJsonInput();
            
            // Validar campos requeridos
            validateRequired($data, ['nombre', 'edad', 'cama']);
            
            // Sanitizar datos
            $patientData = $db->sanitize([
                'nombre' => $data['nombre'],
                'edad' => $data['edad'],
                'peso' => $data['peso'] ?? null,
                'historia_clinica' => $data['historia_clinica'] ?? '',
                'cama' => $data['cama'],
                'fecha_ingreso' => date('Y-m-d H:i:s'),
                'hoja_clinica' => $data['hoja_clinica'] ?? 1,
                'fecha_grafica' => $data['fecha_grafica'] ?? date('Y-m-d'),
                'activo' => 1
            ]);
            
            // Verificar que la cama no esté ocupada
            $camaOcupada = $db->exists('pacientes', 'cama = :cama AND activo = 1', [':cama' => $patientData['cama']]);
            if ($camaOcupada) {
                ApiResponse::json(ApiResponse::error("La cama ya está ocupada", 409));
            }
            
            $newPatientId = $db->insert('pacientes', $patientData);
            
            logAction('patient_created', ['patient_id' => $newPatientId, 'data' => $patientData]);
            
            ApiResponse::json(ApiResponse::success(
                ['id' => $newPatientId, 'patient' => $patientData], 
                "Paciente creado exitosamente"
            ));
            break;
            
        case 'PUT':
            // Actualizar paciente existente
            if (!$patientId) {
                ApiResponse::json(ApiResponse::error("ID del paciente requerido", 400));
            }
            
            $data = getJsonInput();
            
            // Verificar que el paciente existe
            $existingPatient = $db->getById('pacientes', $patientId);
            if (!$existingPatient) {
                ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
            }
            
            // Preparar datos para actualizar (solo campos proporcionados)
            $updateData = [];
            $allowedFields = ['nombre', 'edad', 'peso', 'historia_clinica', 'cama', 'hoja_clinica', 'fecha_grafica', 'activo'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                ApiResponse::json(ApiResponse::error("No hay datos para actualizar", 400));
            }
            
            // Si se está cambiando la cama, verificar disponibilidad
            if (isset($updateData['cama']) && $updateData['cama'] != $existingPatient['cama']) {
                $camaOcupada = $db->exists('pacientes', 'cama = :cama AND activo = 1 AND id != :id', 
                    [':cama' => $updateData['cama'], ':id' => $patientId]);
                if ($camaOcupada) {
                    ApiResponse::json(ApiResponse::error("La cama ya está ocupada", 409));
                }
            }
            
            // Sanitizar datos
            $updateData = $db->sanitize($updateData);
            
            $db->update('pacientes', $updateData, 'id = :id', [':id' => $patientId]);
            
            logAction('patient_updated', ['patient_id' => $patientId, 'data' => $updateData]);
            
            // Obtener paciente actualizado
            $updatedPatient = $db->getById('pacientes', $patientId);
            
            ApiResponse::json(ApiResponse::success($updatedPatient, "Paciente actualizado exitosamente"));
            break;
            
        case 'DELETE':
            // Eliminar paciente (soft delete - marcar como inactivo)
            if (!$patientId) {
                ApiResponse::json(ApiResponse::error("ID del paciente requerido", 400));
            }
            
            // Verificar que el paciente existe
            $existingPatient = $db->getById('pacientes', $patientId);
            if (!$existingPatient) {
                ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
            }
            
            // Marcar como inactivo en lugar de eliminar
            $db->update('pacientes', ['activo' => 0], 'id = :id', [':id' => $patientId]);
            
            logAction('patient_deleted', ['patient_id' => $patientId]);
            
            ApiResponse::json(ApiResponse::success(null, "Paciente marcado como inactivo"));
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en API pacientes: " . $e->getMessage());
    ApiResponse::json(ApiResponse::error("Error interno del servidor: " . $e->getMessage(), 500));
}
?>