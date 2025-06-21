<?php
/*
====================================
ARCHIVO: clear_draft.php
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
    
    if (!$data || !isset($data['box'])) {
        throw new Exception('Número de Box requerido');
    }
    
    $box = $data['box'];
    
    // Validar box
    if (!is_numeric($box) || $box < 1 || $box > 12) {
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
    
    // Eliminar el draft
    $stmt = $pdo->prepare("
        DELETE FROM drafts 
        WHERE user_id = ? AND box = ?
    ");
    
    $result = $stmt->execute([$_SESSION['user_id'], $box]);
    
    if ($result) {
        $deleted_count = $stmt->rowCount();
        echo json_encode([
            'success' => true,
            'message' => $deleted_count > 0 ? 'Draft eliminado correctamente' : 'No había draft para eliminar',
            'deleted_count' => $deleted_count,
            'box' => $box
        ]);
    } else {
        throw new Exception('Error al eliminar draft');
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