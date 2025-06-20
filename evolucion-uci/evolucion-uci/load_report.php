<?php
//load_report.php

date_default_timezone_set('Europe/Madrid');
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once("conexion.php");

header('Content-Type: application/json; charset=utf-8');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
  exit;
}

$user_id = $_SESSION['user_id'];

// Verificar parámetros GET
$box   = isset($_GET['box'])   ? intval($_GET['box'])   : null;
$fecha = isset($_GET['fecha']) ? $_GET['fecha']         : null;

if (!$box || !$fecha) {
  echo json_encode(["success" => false, "message" => "Faltan parámetros"]);
  exit;
}

// Buscar el informe
$sql = "SELECT * FROM informes WHERE user_id = ? AND box = ? AND fecha = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $user_id, $box, $fecha);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  // Eliminar campos internos
  unset($row["id"]);
  unset($row["user_id"]);

  echo json_encode([
    "success" => true,
    "report" => $row
  ]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "Informe no encontrado para ese box y fecha"
  ]);
}
?>
