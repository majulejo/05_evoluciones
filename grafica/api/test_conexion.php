<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Configuración de base de datos
    $host = 'localhost';
    $dbname = 'u724879249_data';
    $username = 'u724879249_data';
    $password = 'Farolill0.1';
    
    // Conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $resultados = [];
    
    // Verificar tabla pacientes
    try {
        $stmt = $pdo->query("SELECT * FROM pacientes LIMIT 5");
        $pacientes = $stmt->fetchAll();
        $resultados['pacientes'] = [
            'existe' => true,
            'count' => count($pacientes),
            'datos' => $pacientes,
            'estructura' => $pdo->query("DESCRIBE pacientes")->fetchAll()
        ];
    } catch (Exception $e) {
        $resultados['pacientes'] = [
            'existe' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Verificar tabla constantes_vitales
    try {
        $stmt = $pdo->query("SELECT * FROM constantes_vitales LIMIT 5");
        $constantes = $stmt->fetchAll();
        $resultados['constantes_vitales'] = [
            'existe' => true,
            'count' => count($constantes),
            'datos' => $constantes,
            'estructura' => $pdo->query("DESCRIBE constantes_vitales")->fetchAll()
        ];
    } catch (Exception $e) {
        $resultados['constantes_vitales'] = [
            'existe' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Verificar tabla datos_oxigenacion
    try {
        $stmt = $pdo->query("SELECT * FROM datos_oxigenacion LIMIT 5");
        $oxigenacion = $stmt->fetchAll();
        $resultados['datos_oxigenacion'] = [
            'existe' => true,
            'count' => count($oxigenacion),
            'datos' => $oxigenacion,
            'estructura' => $pdo->query("DESCRIBE datos_oxigenacion")->fetchAll()
        ];
    } catch (Exception $e) {
        $resultados['datos_oxigenacion'] = [
            'existe' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Conexión y tablas verificadas',
        'database' => $dbname,
        'tablas' => $resultados
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Error de conexión
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a base de datos',
        'error' => $e->getMessage(),
        'config' => [
            'host' => $host,
            'database' => $dbname,
            'username' => $username
        ]
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    // Otros errores
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error general',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>