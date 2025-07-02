<?php
/*
====================================
ARCHIVO: get_latest_report.php
====================================
Obtiene el último informe de un box específico
Busca en ambas tablas: informes_guardados e informes
Zona horaria: Madrid (Europe/Madrid)
*/

date_default_timezone_set('Europe/Madrid');
session_start();

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$box = isset($_GET['box']) ? intval($_GET['box']) : 0;
if ($box < 1 || $box > 12) {
    echo json_encode(['success' => false, 'message' => 'Box inválido']);
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4',
        'u724879249_jamarquez06',
        'Farolill01.',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Buscar en ambas tablas
    $informe = null;
    $tableUsed = '';
    
    // Primero en informes_guardados
    try {
        $stmt = $pdo->prepare("
            SELECT id, box, datos, 
                   DATE_FORMAT(fecha_creacion, '%Y-%m-%d') as fecha, 
                   DATE_FORMAT(fecha_creacion, '%H:%i') as hora
            FROM informes_guardados 
            WHERE user_id = ? AND box = ?
            ORDER BY fecha_creacion DESC 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id'], $box]);
        $informe = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($informe) $tableUsed = 'informes_guardados';
    } catch (PDOException $e) {
        // Tabla no existe, continuar
    }
    
    // Si no se encontró, buscar en informes
    if (!$informe) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, box, datos, 
                       DATE_FORMAT(timestamp, '%Y-%m-%d') as fecha, 
                       DATE_FORMAT(timestamp, '%H:%i') as hora
                FROM informes 
                WHERE user_id = ? AND box = ?
                ORDER BY timestamp DESC 
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['user_id'], $box]);
            $informe = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($informe) $tableUsed = 'informes';
        } catch (PDOException $e) {
            // Tabla no existe
        }
    }

    if ($informe) {
        echo json_encode([
            'success' => true,
            'id' => $informe['id'],
            'box' => $informe['box'],
            'fecha' => $informe['fecha'],
            'hora' => $informe['hora'],
            'datos' => json_decode($informe['datos'], true),
            'table_used' => $tableUsed
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron informes para este box'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>