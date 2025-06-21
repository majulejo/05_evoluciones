<?php
$host = "localhost";
$db = "u724879249_evolucion_uci";
$user = "u724879249_jamarquez06";
$pass = "Farolill01.";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}
?>
