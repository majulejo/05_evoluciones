<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'PHP funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>