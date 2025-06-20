<?php
session_start();
require 'db.php';
date_default_timezone_set('Europe/Madrid');
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bienvenido</title>
</head>
<body>
  <h1>Hola, <?=$_SESSION['user_id']?></h1>
  <p>Estás dentro del sistema. Puedes acceder a:</p>
  <ul>
    <li><a href="app.php">Evolución de Enfermería</a></li>
    <li><a href="balance.php">Balance Hídrico</a></li>
  </ul>
  <a href="logout.php">Cerrar Sesión</a>
</body>
</html>