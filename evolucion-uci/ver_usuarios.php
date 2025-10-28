<?php
session_start();
require_once("conexion.php");

$ADMIN_CODE = "faroladmin2024";

$headers = getallheaders();
if (!isset($headers['X-Admin-Key']) || $headers['X-Admin-Key'] !== $ADMIN_CODE) {
  http_response_code(403);
  echo json_encode(["success" => false, "message" => "Acceso denegado"]);
  exit;
}

$sql = "SELECT id, usuario, user_id FROM usuarios ORDER BY id ASC";
$result = $conn->query($sql);

$usuarios = [];
while ($row = $result->fetch_assoc()) {
  $usuarios[] = $row;
}

echo json_encode(["success" => true, "usuarios" => $usuarios]);
