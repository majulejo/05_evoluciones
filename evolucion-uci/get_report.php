<?php
/*
====================================
ARCHIVO: get_report.php
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
    // Verificar que se proporcione el ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID de informe requerido');
    }
    
    $informe_id = $_GET['id'];
    
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
    
    // Obtener el informe específico
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            user_id, 
            box, 
            datos, 
            fecha_creacion,
            DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha,
            DATE_FORMAT(fecha_creacion, '%H:%i') as hora
        FROM informes 
        WHERE id = ? AND user_id = ? AND activo = 1
    ");
    
    $stmt->execute([$informe_id, $_SESSION['user_id']]);
    $informe = $stmt->fetch();
    
    if (!$informe) {
        throw new Exception('Informe no encontrado o sin permisos');
    }
    
    // Decodificar los datos JSON
    $datos = json_decode($informe['datos'], true);
    if (!$datos) {
        throw new Exception('Error al decodificar datos del informe');
    }
    
    echo json_encode([
        'success' => true,
        'id' => $informe['id'],
        'box' => $informe['box'],
        'datos' => $datos,
        'fecha' => $informe['fecha'],
        'hora' => $informe['hora'],
        'fecha_creacion' => $informe['fecha_creacion'],
        'timezone' => date_default_timezone_get()
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