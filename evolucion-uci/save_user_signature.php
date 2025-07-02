<?php
/*
====================================
ARCHIVO: save_user_signature.php
====================================
Guarda firmas de usuario en la tabla user_signatures
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
    
    if (!$data || !isset($data['firma'])) {
        throw new Exception('Firma requerida');
    }
    
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                   'u724879249_jamarquez06', 'Farolill01.', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    // Verificar si ya existe una firma para este usuario
    $checkStmt = $pdo->prepare("
        SELECT user_id FROM user_signatures 
        WHERE user_id = ?
    ");
    $checkStmt->execute([$_SESSION['user_id']]);
    
    if ($checkStmt->rowCount() > 0) {
        // Actualizar firma existente
        $stmt = $pdo->prepare("
            UPDATE user_signatures 
            SET firma = ?, fecha_actualizacion = CURRENT_TIMESTAMP 
            WHERE user_id = ?
        ");
        $result = $stmt->execute([
            $data['firma'],
            $_SESSION['user_id']
        ]);
        $action = 'actualizada';
    } else {
        // Insertar nueva firma
        $stmt = $pdo->prepare("
            INSERT INTO user_signatures (user_id, firma, fecha_actualizacion) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $result = $stmt->execute([
            $_SESSION['user_id'],
            $data['firma']
        ]);
        $action = 'guardada';
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => "Firma $action correctamente",
            'action' => $action
        ]);
    } else {
        throw new Exception('Error al guardar firma');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>