<?php
$host = "localhost";
$dbname = "u724879249_evolucion_uci";
$username = "u724879249_jamarquez06";
$password = "Farolill01.";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>