<?php
/*
====================================
ARCHIVO: get_report.php
====================================
Obtiene un informe específico por ID
Busca en ambas tablas: informes_guardados e informes
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
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID de informe requerido');
    }
    
    $informe_id = $_GET['id'];
    
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                   'u724879249_jamarquez06', 'Farolill01.', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    // Buscar en ambas tablas
    $informe = null;
    $tableUsed = '';
    
    // Primero intentar en informes_guardados
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, user_id, box, datos, fecha_creacion,
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha,
                DATE_FORMAT(fecha_creacion, '%H:%i') as hora
            FROM informes_guardados 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$informe_id, $_SESSION['user_id']]);
        $informe = $stmt->fetch();
        if ($informe) $tableUsed = 'informes_guardados';
    } catch (PDOException $e) {
        // Tabla no existe, continuar
    }
    
    // Si no se encontró, buscar en informes
    if (!$informe) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    id, user_id, box, datos, timestamp as fecha_creacion,
                    DATE_FORMAT(timestamp, '%d/%m/%Y') as fecha,
                    DATE_FORMAT(timestamp, '%H:%i') as hora
                FROM informes 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$informe_id, $_SESSION['user_id']]);
            $informe = $stmt->fetch();
            if ($informe) $tableUsed = 'informes';
        } catch (PDOException $e) {
            // Tabla no existe
        }
    }
    
    if (!$informe) {
        throw new Exception('Informe no encontrado');
    }
    
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
        'table_used' => $tableUsed
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>