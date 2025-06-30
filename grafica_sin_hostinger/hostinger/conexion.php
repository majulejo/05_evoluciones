<?php
$host = "localhost";
$db = "u724879249_grafica_uci";
$user = "u724879249_grafica_user";
$pass = "Periquit0.1";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}
?>
