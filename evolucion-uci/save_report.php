<?php
/*
====================================
ARCHIVO: save_report.php
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
    // Configuración de base de datos
    $host = 'localhost';
    $dbname = 'u724879249_evolucion_uci';
    $username = 'u724879249_jamarquez06';
    $password = 'Farolill01.';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Establecer zona horaria en MySQL también
    $pdo->exec("SET time_zone = '+02:00'"); // Horario de verano Madrid
    
    // Leer datos JSON del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar datos requeridos
    if (!isset($data['id']) || !isset($data['user_id']) || !isset($data['box']) || !isset($data['datos'])) {
        throw new Exception('Faltan datos requeridos');
    }
    
    // Crear timestamp con zona horaria de Madrid
    $timestamp = date('Y-m-d H:i:s');
    
    // Insertar en base de datos
    $stmt = $pdo->prepare("
        INSERT INTO informes (id, user_id, box, datos, fecha_creacion, activo) 
        VALUES (?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
        datos = VALUES(datos), 
        fecha_creacion = VALUES(fecha_creacion)
    ");
    
    $result = $stmt->execute([
        $data['id'],
        $data['user_id'],
        $data['box'],
        json_encode($data['datos']),
        $timestamp
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'id' => $data['id'],
            'timestamp' => $timestamp,
            'timezone' => date_default_timezone_get(),
            'message' => 'Informe guardado correctamente'
        ]);
    } else {
        throw new Exception('Error al insertar en base de datos');
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