<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test simple para verificar que PHP funciona
try {
    echo json_encode([
        'success' => true,
        'message' => 'PHP funcionando correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'test_calculation' => [
            'glucemia_150' => 'NADA',
            'glucemia_200' => '6 U.I. s/c',
            'glucemia_400' => 'Perfusión I.V.'
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>