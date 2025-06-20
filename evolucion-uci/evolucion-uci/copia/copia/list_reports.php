<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$user_id = $_SESSION['user_id']; // ✅ ¡Obligatorio!

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 0);

try {
    $stmt = $db->prepare(
        "SELECT id,
                box,
                DATE(CONVERT_TZ(timestamp,'+00:00','+02:00')) AS fecha,
                TIME(CONVERT_TZ(timestamp,'+00:00','+02:00')) AS hora
         FROM reports
         WHERE user_id = ?
         ORDER BY timestamp DESC"
    );
    $stmt->bind_param('s', $user_id);
    $stmt->execute();

    $out = [];
    foreach ($stmt->get_result() as $r) {
        $out[] = [
            'id'    => $r['id'],
            'box'   => (int)$r['box'],
            'fecha' => $r['fecha'],
            'hora'  => $r['hora']
        ];
    }

    echo json_encode(['success' => true, 'reports' => $out]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
