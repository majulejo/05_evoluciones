<?php
// archivo: api/crear_paciente.php
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

    // Validaciones
    if ($box <= 0 || empty($nombre) || $edad <= 0 || empty($historia)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos']);
        exit;
    }

    $pdo = obtenerConexionBD();

    // Verificar que el box esté libre
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pacientes WHERE box = ? AND activo = 1");
    $stmt->execute([$box]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'El box ya está ocupado']);
        exit;
    }

    // Verificar que no existe paciente con el mismo número de historia activo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pacientes WHERE numero_historia = ? AND activo = 1");
    $stmt->execute([$historia]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un paciente activo con este número de historia']);
        exit;
    }

    // Insertar nuevo paciente
    $stmt = $pdo->prepare("
        INSERT INTO pacientes (box, nombre_completo, edad, peso, numero_historia, fecha_ingreso, activo) 
        VALUES (?, ?, ?, ?, ?, NOW(), 1)
    ");
    
    $stmt->execute([$box, $nombre, $edad, $peso, $historia]);
    $paciente_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Paciente ingresado correctamente',
        'id' => $paciente_id,
        'box' => $box
    ]);

} catch (Exception $e) {
    error_log('Error en crear_paciente.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al crear paciente: ' . $e->getMessage()
    ]);
}
?>

