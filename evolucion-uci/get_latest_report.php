<?php
date_default_timezone_set('Europe/Madrid');
session_start();
// ... resto del código
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// 2. Obtener parámetro box
$box = isset($_GET['box']) ? intval($_GET['box']) : 0;
if ($box < 1 || $box > 12) {
    echo json_encode(['success' => false, 'message' => 'Box inválido']);
    exit;
}

// 3. Conectar a la base de datos
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4',
        'u724879249_jamarquez06',
        'Farolill01.'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Buscar último informe
    $stmt = $pdo->prepare("
        SELECT id, box, datos, DATE_FORMAT(timestamp, '%Y-%m-%d') as fecha, 
               DATE_FORMAT(timestamp, '%H:%i') as hora
        FROM reports 
        WHERE user_id = ? AND box = ?
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $box]);
    $informe = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($informe) {
        // 5. Devolver informe encontrado
        echo json_encode([
            'success' => true,
            'id' => $informe['id'],
            'box' => $informe['box'],
            'fecha' => $informe['fecha'],
            'hora' => $informe['hora'],
            'datos' => json_decode($informe['datos'], true)
        ]);
    } else {
        // 6. No hay informes
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron informes para este box'
        ]);
    }
} catch (PDOException $e) {
    // 7. Manejar errores de base de datos
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>