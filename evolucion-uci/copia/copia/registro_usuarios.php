<?php
header("Content-Type: application/json");
require_once("conexion.php");

session_start();
$ADMIN_CODE = "faroladmin2024";

$headers = getallheaders();
if (!isset($headers['X-Admin-Key']) || $headers['X-Admin-Key'] !== $ADMIN_CODE) {
  http_response_code(403);
  echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
  exit;
}

header("Content-Type: application/json");
require_once("conexion.php");

$input = json_decode(file_get_contents("php://input"), true);
$usuario = trim($input["usuario"]);
$clave = trim($input["clave"]);

if (!$usuario || !$clave) {
  echo json_encode(["success" => false, "message" => "Faltan datos."]);
  exit;
}

$hash = password_hash($clave, PASSWORD_BCRYPT);
$user_id = uniqid("uid_");

try {
  $stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave, user_id) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $usuario, $hash, $user_id);
  $stmt->execute();
  echo json_encode(["success" => true, "message" => "Usuario registrado correctamente."]);
} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
session_start();
require_once("conexion.php");

$ADMIN_CODE = "faroladmin2024"; // misma clave usada en el frontend

$headers = getallheaders();
if (!isset($headers['X-Admin-Key']) || $headers['X-Admin-Key'] !== $ADMIN_CODE) {
  http_response_code(403);
  echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
  exit;
}
?>
