<?php
/*
====================================
ARCHIVO: get_draft.php
====================================
Recupera borradores de la tabla drafts
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
    if (!isset($_GET['box'])) {
        throw new Exception('Número de box requerido');
    }
    
    $box = intval($_GET['box']);
    
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                   'u724879249_jamarquez06', 'Farolill01.', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    $stmt = $pdo->prepare("
        SELECT user_id, box, datos, timestamp 
        FROM drafts 
        WHERE user_id = ? AND box = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $box]);
    $draft = $stmt->fetch();
    
    if ($draft) {
        $datos = json_decode($draft['datos'], true);
        echo json_encode([
            'success' => true,
            'box' => $draft['box'],
            'datos' => $datos ?: [],
            'updated_at' => $draft['updated_at']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No hay draft guardado para este box'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>