<?php
/*
====================================
ARCHIVO: delete_reports.php
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
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('ID de informe requerido');
    }
    
    $informe_id = $data['id'];
    
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
    
    // Verificar que el informe pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT id FROM informes 
        WHERE id = ? AND user_id = ? AND activo = 1
    ");
    $stmt->execute([$informe_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Informe no encontrado o sin permisos');
    }
    
    // Marcar como inactivo (eliminación lógica)
    $stmt = $pdo->prepare("
        UPDATE informes 
        SET activo = 0, fecha_eliminacion = ? 
        WHERE id = ? AND user_id = ?
    ");
    
    $timestamp = date('Y-m-d H:i:s');
    $result = $stmt->execute([$timestamp, $informe_id, $_SESSION['user_id']]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Informe eliminado correctamente',
            'id' => $informe_id,
            'timestamp' => $timestamp
        ]);
    } else {
        throw new Exception('No se pudo eliminar el informe');
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