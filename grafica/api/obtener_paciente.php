<?php
// archivo: api/obtener_paciente.php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $box = isset($_GET['box']) ? intval($_GET['box']) : 0;

    if ($box <= 0) {
        echo json_encode(['success' => false, 'message' => 'Box inválido']);
        exit;
    }

    $pdo = obtenerConexionBD();
    
    // CORREGIDO: usar numero_box en lugar de box
    $stmt = $pdo->prepare("
        SELECT nombre_completo, edad, peso, numero_historia, fecha_ingreso, numero_box as box
        FROM pacientes 
        WHERE numero_box = ? AND estado = 'activo' 
        ORDER BY fecha_ingreso DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$box]);
    $paciente = $stmt->fetch();

    if ($paciente) {
        echo json_encode([
            'success' => true, 
            'data' => $paciente
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No hay paciente activo en este box'
        ]);
    }

} catch (Exception $e) {
    error_log('Error en obtener_paciente.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
