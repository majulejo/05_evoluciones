<?php
/*
====================================
ARCHIVO: get_user_signature.php
====================================
Recupera firmas de usuario de la tabla user_signatures
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
    
    $stmt = $pdo->prepare("
        SELECT firma, fecha_actualizacion 
        FROM user_signatures 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $signature = $stmt->fetch();
    
    if ($signature) {
        echo json_encode([
            'success' => true,
            'firma' => $signature['firma'],
            'fecha_actualizacion' => $signature['fecha_actualizacion']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No hay firma guardada para este usuario',
            'firma' => ''
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'firma' => ''
    ]);
}
?>