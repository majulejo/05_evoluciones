<?php
/*
====================================
ARCHIVO: get_user_signature.php
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
    
    // Establecer zona horaria en MySQL
    $pdo->exec("SET time_zone = '+02:00'");
    
    // Obtener la firma del usuario
    $stmt = $pdo->prepare("
        SELECT 
            firma,
            fecha_actualizacion,
            DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_formateada
        FROM user_signatures 
        WHERE user_id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $signature = $stmt->fetch();
    
    if (!$signature) {
        // No hay firma guardada
        echo json_encode([
            'success' => true,
            'firma' => '',
            'message' => 'No hay firma guardada'
        ]);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'firma' => $signature['firma'] ?? '',
        'fecha_actualizacion' => $signature['fecha_actualizacion'],
        'fecha_formateada' => $signature['fecha_formateada']
    ]);
    
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