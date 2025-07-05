<?php
// archivo: api/guardar_paciente.php (actualizar datos existentes)
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $box = isset($input['box']) ? intval($input['box']) : 0;
    $nombre = isset($input['nombre_completo']) ? trim($input['nombre_completo']) : '';
    $edad = isset($input['edad']) ? intval($input['edad']) : 0;
    $peso = isset($input['peso']) ? floatval($input['peso']) : null;
    $historia = isset($input['numero_historia']) ? trim($input['numero_historia']) : '';

    if ($box <= 0) {
        echo json_encode(['success' => false, 'message' => 'Box inválido']);
        exit;
    }

    $pdo = obtenerConexionBD();

    // Actualizar datos del paciente
    $stmt = $pdo->prepare("
        UPDATE pacientes 
        SET nombre_completo = ?, edad = ?, peso = ?, numero_historia = ?
        WHERE box = ? AND activo = 1
    ");
    
    $resultado = $stmt->execute([$nombre, $edad, $peso, $historia, $box]);

    if ($resultado && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Datos del paciente actualizados correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró paciente activo en este box o no hubo cambios'
        ]);
    }

} catch (Exception $e) {
    error_log('Error en guardar_paciente.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al actualizar paciente: ' . $e->getMessage()
    ]);
}
?>

