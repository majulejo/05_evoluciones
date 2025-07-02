<?php
/*
====================================
ARCHIVO: list_reports.php
====================================
Lista informes guardados del usuario actual
Compatible con informes_guardados e informes
Zona horaria: Madrid (Europe/Madrid)
*/

session_start();
date_default_timezone_set('Europe/Madrid');

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                   'u724879249_jamarquez06', 'Farolill01.', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    // Detectar tabla disponible
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tableName = in_array('informes_guardados', $tables) ? 'informes_guardados' : 'informes';
    
    // Consulta adaptada según la tabla
    if ($tableName === 'informes_guardados') {
        $stmt = $pdo->prepare("
            SELECT 
                id, box, 
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha,
                DATE_FORMAT(fecha_creacion, '%H:%i') as hora,
                fecha_creacion
            FROM informes_guardados 
            WHERE user_id = ? 
            ORDER BY fecha_creacion DESC 
            LIMIT 50
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                id, box, 
                DATE_FORMAT(timestamp, '%d/%m/%Y') as fecha,
                DATE_FORMAT(timestamp, '%H:%i') as hora,
                timestamp as fecha_creacion
            FROM informes 
            WHERE user_id = ? 
            ORDER BY timestamp DESC 
            LIMIT 50
        ");
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    $informes = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'reports' => $informes,
        'count' => count($informes),
        'table_used' => $tableName
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>