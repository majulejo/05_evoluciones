<?php
// config.php - Configuración de base de datos
// Datos de conexión a la base de datos de Hostinger
$host = 'localhost'; // O la IP que te proporcione Hostinger
$dbname = 'u724879249_data'; // Nombre de tu base de datos
$username = 'u724879249_data'; // Nombre de usuario
$password = 'Farolill0.1'; // Tu contraseña

// Configuración adicional
$charset = 'utf8mb4';

// DSN para PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Opciones de PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Función para obtener conexión (requerida por gestionar_pacientes.php)
function obtenerConexionBD() {
    global $dsn, $username, $password, $options;
    try {
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        throw new Exception('Error de conexión a la base de datos');
    }
}

// Crear conexión principal
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die('Error de conexión a la base de datos');
}

// Crear tabla pacientes_boxes si no existe
$createTableBoxes = "
CREATE TABLE IF NOT EXISTS pacientes_boxes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_box INT NOT NULL UNIQUE,
    nombre_paciente VARCHAR(255) DEFAULT NULL,
    fecha_ingreso DATETIME DEFAULT NULL,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_numero_box (numero_box),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Crear tabla pacientes (para los datos detallados)
$createTablePacientes = "
CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_box INT NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    edad INT NOT NULL,
    peso INT DEFAULT NULL,
    numero_historia VARCHAR(100) NOT NULL,
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    INDEX idx_numero_box (numero_box),
    INDEX idx_estado (estado),
    INDEX idx_numero_historia (numero_historia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    // Crear ambas tablas
    $pdo->exec($createTableBoxes);
    $pdo->exec($createTablePacientes);
    
    // Insertar boxes iniciales si no existen
    $checkBoxes = $pdo->query("SELECT COUNT(*) as count FROM pacientes_boxes");
    $boxCount = $checkBoxes->fetch()['count'];
    
    if ($boxCount == 0) {
        $insertInitial = "INSERT INTO pacientes_boxes (numero_box, nombre_paciente, activo) VALUES ";
        $values = [];
        for ($i = 1; $i <= 12; $i++) {
            $values[] = "($i, NULL, TRUE)";
        }
        $insertInitial .= implode(', ', $values);
        $pdo->exec($insertInitial);
    }
} catch (PDOException $e) {
    error_log("Error al crear tablas: " . $e->getMessage());
}
?>