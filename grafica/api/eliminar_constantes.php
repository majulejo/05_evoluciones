<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $pdo = obtenerConexionBD();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $box = $input['box'] ?? '';
    $hora = $input['hora'] ?? '';
    
    if (empty($box) || empty($hora)) {
        throw new Exception('Box y hora son requeridos');
    }
    
    // Calcular fecha del turno
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');
    
    if ($hora_actual < '08:00:00') {
        $fecha_actual = date('Y-m-d', strtotime('-1 day'));
    }
    
    // Eliminar registro
    $stmt = $pdo->prepare("
        DELETE FROM constantes_vitales 
        WHERE numero_box = ? AND hora = ? AND fecha_hoja = ?
    ");
    $stmt->execute([$box, $hora, $fecha_actual]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Constantes eliminadas correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>