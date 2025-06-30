<?php
// config.php - Configuración general del sistema UCI
date_default_timezone_set('Europe/Madrid');

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Configuración de la base de datos - CREDENCIALES CORREGIDAS
define('DB_HOSTS', ['localhost', 'mysql.jolejuma.es', 'jolejuma.es', '127.0.0.1']);
define('DB_NAME', 'u724879249_grafica_uci');
define('DB_USER', 'u724879249_grafica_user');  // CORREGIDO: sin la doble 'r'
define('DB_PASS', 'Periquit0.1');

// Configuración del sistema
define('SYSTEM_NAME', 'UCI Gráficas - Sistema de Enfermería');
define('SYSTEM_VERSION', '1.0.0');
define('BASE_URL', 'http://jolejuma.es/grafica');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>