<?php
/*
====================================
ARCHIVO: save_user_signature.php
====================================
*/

session_start();
date_default_timezone_set('Europe/Madrid'); // ⭐ ZONA HORARIA DE MADRID

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

try {
    // Leer datos JSON del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar datos requeridos
    if (!isset($data['user_id']) || !isset($data['firma'])) {
        throw new Exception('Faltan datos requeridos (user_id, firma)');
    }
    
    // Verificar que el user_id coincida con la sesión
    if ($data['user_id'] != $_SESSION['user_id']) {
        throw new Exception('ID de usuario no coincide con la sesión');
    }
    
    // Configuración de base de datos
    $host = 'localhost';
    $dbname = 'u724879249_evolucion_uci';
    $username = 'u724879249_jamarquez06';
    $password = 'Farolill01.';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Establecer zona horaria en MySQL
    $pdo->exec("SET time_zone = '+02:00'");
    
    // Crear timestamp con zona horaria de Madrid
    $timestamp = date('Y-m-d H:i:s');
    
    // Insertar o actualizar firma
    $stmt = $pdo->prepare("
        INSERT INTO user_signatures (user_id, firma, fecha_actualizacion) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        firma = VALUES(firma), 
        fecha_actualizacion = VALUES(fecha_actualizacion)
    ");
    
    $result = $stmt->execute([
        $data['user_id'],
        $data['firma'],
        $timestamp
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Firma guardada correctamente',
            'user_id' => $data['user_id'],
            'timestamp' => $timestamp
        ]);
    } else {
        throw new Exception('Error al guardar firma');
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>