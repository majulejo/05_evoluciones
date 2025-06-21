<?php
/*
====================================
ARCHIVO: list_reports.php
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
    
    // Obtener informes del usuario actual
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            box, 
            DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha,
            DATE_FORMAT(fecha_creacion, '%H:%i') as hora,
            fecha_creacion
        FROM informes 
        WHERE user_id = ? AND activo = 1 
        ORDER BY fecha_creacion DESC 
        LIMIT 50
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $informes = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'reports' => $informes,
        'count' => count($informes),
        'timezone' => date_default_timezone_get()
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