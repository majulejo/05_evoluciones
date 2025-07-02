<?php
/*
====================================
ARCHIVO: clear_draft.php
====================================
Elimina borradores de la tabla drafts
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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['box'])) {
        throw new Exception('Número de box requerido');
    }
    
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                   'u724879249_jamarquez06', 'Farolill01.', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    $stmt = $pdo->prepare("
        DELETE FROM drafts 
        WHERE user_id = ? AND box = ?
    ");
    $result = $stmt->execute([$_SESSION['user_id'], $data['box']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Draft eliminado correctamente',
        'deleted_count' => $stmt->rowCount()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>