<?php
date_default_timezone_set('Europe/Madrid');
require 'db.php';

$user_id = $_GET['user_id'] ?? null;

if ($user_id === null) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Falta user_id']);
  exit;
}

$stmt = $pdo->prepare("SELECT datos FROM last_patient WHERE user_id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
  echo json_encode([
    'success' => true,
    'datos' => $row['datos']
  ]);
} else {
  echo json_encode([
    'success' => false,
    'message' => 'No hay datos guardados'
  ]);
}
?>