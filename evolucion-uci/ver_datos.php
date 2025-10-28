<?php
date_default_timezone_set('Europe/Madrid');

session_start();
require_once '../db.php';

if (empty($_SESSION['user_id'])) {
    die("Acceso denegado");
}

$stmt = $db->prepare("SELECT * FROM datos_balance WHERE usuario_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo "<pre>";
print_r($result);
echo "</pre>";