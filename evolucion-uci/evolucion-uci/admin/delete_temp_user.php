<?php
session_start();
require_once 'conexion.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$usuario = trim($input["usuario"] ?? '');

if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'Falta el usuario.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM user_password WHERE usuario = ?");
$stmt->bind_param("s", $usuario);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Usuario temporal eliminado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $stmt->error]);
}
?>