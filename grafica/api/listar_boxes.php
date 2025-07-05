<?php
// archivo: api/listar_boxes.php (útil para el índice)
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $pdo = obtenerConexionBD();
    
    // Obtener estado de todos los boxes (asumiendo que tienes 10 boxes)
    $stmt = $pdo->prepare("
        SELECT 
            p.box,
            p.nombre_completo,
            p.fecha_ingreso,
            CASE WHEN p.box IS NOT NULL THEN 1 ELSE 0 END as ocupado
        FROM (SELECT 1 as box UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
              UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) boxes
        LEFT JOIN pacientes p ON boxes.box = p.box AND p.activo = 1
        ORDER BY boxes.box
    ");
    
    $stmt->execute();
    $boxes = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $boxes
    ]);

} catch (Exception $e) {
    error_log('Error en listar_boxes.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al obtener estado de boxes: ' . $e->getMessage()
    ]);
}
?>