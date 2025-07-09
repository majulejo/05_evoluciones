<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $historialFile = 'data/historial.json';
    
    if (!file_exists($historialFile)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $historial = json_decode(file_get_contents($historialFile), true);
    
    // Ordenar por fecha de alta (más reciente primero)
    usort($historial, function($a, $b) {
        return strtotime($b['fecha_alta']) - strtotime($a['fecha_alta']);
    });

    echo json_encode(['success' => true, 'data' => $historial]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener historial: ' . $e->getMessage()]);
}
?>