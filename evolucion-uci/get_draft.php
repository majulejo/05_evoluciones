<?php
/*
====================================
ARCHIVO: get_draft.php
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
    // Verificar que se proporcione el box
    if (!isset($_GET['box']) || empty($_GET['box'])) {
        throw new Exception('Número de Box requerido');
    }
    
    $box = $_GET['box'];
    
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
    
    // Obtener el draft específico
    $stmt = $pdo->prepare("
        SELECT 
            datos, 
            fecha_actualizacion,
            DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_formateada
        FROM drafts 
        WHERE user_id = ? AND box = ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $box]);
    $draft = $stmt->fetch();
    
    if (!$draft) {
        // No hay draft guardado para este box
        echo json_encode([
            'success' => true,
            'datos' => null,
            'message' => 'No hay draft guardado para este box'
        ]);
        exit();
    }
    
    // Decodificar los datos JSON
    $datos = json_decode($draft['datos'], true);
    if (!$datos) {
        // Si hay error al decodificar, devolver datos vacíos
        $datos = [];
    }
    
    echo json_encode([
        'success' => true,
        'datos' => $datos,
        'fecha_actualizacion' => $draft['fecha_actualizacion'],
        'fecha_formateada' => $draft['fecha_formateada'],
        'box' => $box
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