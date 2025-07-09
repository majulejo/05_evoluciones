<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Solo permitir POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Use POST.');
    }
    
    // Obtener datos JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar campos requeridos
    if (!isset($data['box']) || !isset($data['hora'])) {
        throw new Exception('Campos box y hora son requeridos');
    }
    
    $box = intval($data['box']);
    $hora = $data['hora'];
    $hoja = isset($data['hoja']) ? intval($data['hoja']) : 1;
    
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
    
    // Eliminar constantes vitales para la hora especificada
    $sql = "DELETE FROM constantes_vitales WHERE numero_box = :box AND hora = :hora AND hoja = :hoja";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'box' => $box,
        'hora' => $hora,
        'hoja' => $hoja
    ]);
    
    $filasAfectadas = $stmt->rowCount();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => $filasAfectadas > 0 ? 'Constantes eliminadas correctamente' : 'No se encontraron constantes para eliminar',
        'box' => $box,
        'hora' => $hora,
        'hoja' => $hoja,
        'deleted_rows' => $filasAfectadas
    ]);
    
} catch (PDOException $e) {
    // Error de base de datos
    error_log("Error DB en eliminar_constantes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_type' => 'database'
    ]);
    
} catch (Exception $e) {
    // Otros errores
    error_log("Error en eliminar_constantes: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'validation'
    ]);
}
?>