<?php
date_default_timezone_set('Europe/Madrid');


require 'db.php'; // conexión a la BD

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['user_id']) && isset($data['datos'])) {
  $userId = $pdo->quote($data['user_id']);
  $datosJson = json_encode($data['datos']);

  // Eliminar datos anteriores
  $stmt = $pdo->prepare("DELETE FROM last_patient WHERE user_id = ?");
  $stmt->execute([$userId]);

  // Insertar nuevos datos
  $stmt = $pdo->prepare("INSERT INTO last_patient (user_id, datos) VALUES (?, ?)");
  $stmt->execute([$userId, $datosJson]);

  echo json_encode(['success' => true]);
} else {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
}
?>