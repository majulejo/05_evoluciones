<?php
$host = "localhost";
$db = "u724879249_evolucion_uci";
$user = "u724879249_jamarquez06";
$pass = "Farolill01.";

$db = new mysqli($host,$user,$pass,$db);
if ($db->connect_error) {
    throw new Exception('Error MySQL: '.$db->connect_error);
}
