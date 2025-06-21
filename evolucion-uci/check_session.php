<?php
// check_session.php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar si la sesión está activa
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => true,
        'user_id' => $_SESSION['user_id'],
        'usuario' => $_SESSION['usuario'] ?? ''
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'message' => 'Sesión no válida'
    ]);
}
?>