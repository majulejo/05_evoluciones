<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$firma = $data['firma'] ?? '';

$stmt = $pdo->prepare("UPDATE usuarios SET firma = ? WHERE id = ?");
$stmt->execute([$firma, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
?>