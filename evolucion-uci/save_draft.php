<?php
/*
====================================
ARCHIVO: save_draft.php
====================================
Guarda borradores en la tabla drafts
Zona horaria: Madrid (Europe/Madrid)
CORREGIDO: Usa 'timestamp' en lugar de 'updated_at'
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
    $input = '';
    $data = null;
    
    // Detectar si viene de sendBeacon (FormData) o fetch normal (JSON)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['data'])) {
            // Viene de sendBeacon con FormData (al cerrar navegador)
            $input = $_POST['data'];
            error_log("📡 sendBeacon recibido: " . substr($input, 0, 50) . "...");
        } else {
            // Viene de fetch normal con JSON (guardado automático)
            $input = file_get_contents('php://input');
            error_log("💾 fetch normal recibido: " . substr($input, 0, 50) . "...");
        }
    }
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }
    
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['box']) || !isset($data['datos'])) {
        throw new Exception('Datos requeridos faltantes');
    }
    
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                   'u724879249_jamarquez06', 'Farolill01.', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    // Verificar si ya existe un draft para este usuario y box
    $checkStmt = $pdo->prepare("
        SELECT user_id FROM drafts 
        WHERE user_id = ? AND box = ?
    ");
    $checkStmt->execute([$_SESSION['user_id'], $data['box']]);
    
    // Convertir datos a JSON
    $datos_json = json_encode($data['datos']);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al codificar datos JSON: ' . json_last_error_msg());
    }
    
    if ($checkStmt->rowCount() > 0) {
        // Actualizar draft existente - USAR 'timestamp' NO 'updated_at'
        $stmt = $pdo->prepare("
            UPDATE drafts 
            SET datos = ?, timestamp = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND box = ?
        ");
        $result = $stmt->execute([
            $datos_json,
            $_SESSION['user_id'],
            $data['box']
        ]);
        $action = 'actualizado';
    } else {
        // Insertar nuevo draft - USAR 'timestamp' NO 'updated_at'
        $stmt = $pdo->prepare("
            INSERT INTO drafts (user_id, box, datos, timestamp) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $result = $stmt->execute([
            $_SESSION['user_id'],
            $data['box'],
            $datos_json
        ]);
        $action = 'guardado';
    }
    
    if ($result) {
        // Log detallado para debugging
        $caracteres = strlen($datos_json);
        $timestamp = isset($data['timestamp']) ? date('H:i:s', $data['timestamp']/1000) : date('H:i:s');
        error_log("✅ Draft $action - User: {$_SESSION['user_id']}, Box: {$data['box']}, Chars: $caracteres, Time: $timestamp");
        
        echo json_encode([
            'success' => true,
            'message' => "Draft $action correctamente",
            'box' => $data['box'],
            'action' => $action,
            'timestamp' => time() * 1000,
            'caracteres' => $caracteres
        ]);
    } else {
        throw new Exception('Error al guardar draft');
    }
    
} catch (PDOException $e) {
    error_log("❌ Error BD en save_draft.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("❌ Error en save_draft.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>