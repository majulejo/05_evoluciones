<?php
// config_simple.php - Configuración simplificada

$host = 'localhost';
$dbname = 'u724879249_data';
$username = 'u724879249_data';
$password = 'Farolill0.1';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Crear tabla si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pacientes_boxes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero_box INT NOT NULL UNIQUE,
            nombre_paciente VARCHAR(255) DEFAULT NULL,
            fecha_ingreso DATETIME DEFAULT NULL,
            fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Insertar boxes iniciales si están vacíos
    $check = $pdo->query("SELECT COUNT(*) as count FROM pacientes_boxes")->fetch();
    if ($check['count'] == 0) {
        for ($i = 1; $i <= 12; $i++) {
            $pdo->exec("INSERT INTO pacientes_boxes (numero_box, activo) VALUES ($i, TRUE)");
        }
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>