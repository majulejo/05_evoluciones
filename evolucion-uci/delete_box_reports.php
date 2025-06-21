<?php
/*
====================================
ARCHIVO: delete_box_reports.php
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
    
    // Validar que el box sea un número válido
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
    
    // Contar informes que se van a eliminar
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM informes 
        WHERE box = ? AND user_id = ? AND activo = 1
    ");
    $stmt->execute([$box, $_SESSION['user_id']]);
    $count = $stmt->fetch()['total'];
    
    if ($count == 0) {
        echo json_encode([
            'success' => true,
            'message' => "No hay informes activos en el Box {$box} para eliminar",
            'deleted_count' => 0
        ]);
        exit();
    }
    
    // Marcar todos los informes del box como inactivos
    $stmt = $pdo->prepare("
        UPDATE informes 
        SET activo = 0, fecha_eliminacion = ? 
        WHERE box = ? AND user_id = ? AND activo = 1
    ");
    
    $timestamp = date('Y-m-d H:i:s');
    $result = $stmt->execute([$timestamp, $box, $_SESSION['user_id']]);
    
    if ($result) {
        $deleted_count = $stmt->rowCount();
        echo json_encode([
            'success' => true,
            'message' => "Se eliminaron {$deleted_count} informes del Box {$box}",
            'deleted_count' => $deleted_count,
            'box' => $box,
            'timestamp' => $timestamp
        ]);
    } else {
        throw new Exception('No se pudieron eliminar los informes');
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