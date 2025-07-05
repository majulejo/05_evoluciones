<?php
header('Content-Type: application/json');

require_once 'config.php';

try {
    $resultado = probarConexion();
    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>