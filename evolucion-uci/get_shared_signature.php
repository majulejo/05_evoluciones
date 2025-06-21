<?php
session_start();
date_default_timezone_set('Europe/Madrid');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Conectar a BD y obtener firma
$stmt = $pdo->prepare("SELECT firma FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$firma = $stmt->fetchColumn();

echo json_encode(['success' => true, 'firma' => $firma ?: '']);
?>