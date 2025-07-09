<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u724879249_data');  
define('DB_USER', 'u724879249_data');   
define('DB_PASS', 'Farolill0.1'); 

// Función para obtener conexión PDO
function obtenerConexionBD() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Agregar más información de debug
        error_log('Error de conexión BD: ' . $e->getMessage());
        throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
    }
}

// Función para probar la conexión (opcional, para debug)
function probarConexion() {
    try {
        $pdo = obtenerConexionBD();
        return ['success' => true, 'message' => 'Conexión exitosa'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>