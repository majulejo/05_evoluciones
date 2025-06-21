<?php
/*
====================================
ARCHIVO: save_draft.php
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
    if (!isset($data['box']) || !isset($data['datos']) || !isset($data['user_id'])) {
        throw new Exception('Faltan datos requeridos (box, datos, user_id)');
    }
    
    // Validar box
    if (!is_numeric($data['box']) || $data['box'] < 1 || $data['box'] > 12) {
        throw new Exception('Número de Box inválido');
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
    
    // Insertar o actualizar draft
    $stmt = $pdo->prepare("
        INSERT INTO drafts (user_id, box, datos, fecha_actualizacion) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        datos = VALUES(datos), 
        fecha_actualizacion = VALUES(fecha_actualizacion)
    ");
    
    $result = $stmt->execute([
        $data['user_id'],
        $data['box'],
        json_encode($data['datos']),
        $timestamp
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Draft guardado correctamente',
            'box' => $data['box'],
            'timestamp' => $timestamp
        ]);
    } else {
        throw new Exception('Error al guardar draft');
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