<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$user_id = $_SESSION['user_id']; // ✅ tomamos el user_id de la sesión

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 0);

try {
    $in = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

    foreach(['id', 'box', 'datos'] as $k){
        if(empty($in[$k])) throw new Exception("Campo '$k' requerido");
    }

    $id     = $in['id'];
    $box    = (int)$in['box'];
    $datos  = json_encode($in['datos'], JSON_UNESCAPED_UNICODE);
    $ts     = $in['timestamp'] ?? date('c');

    $sql = "INSERT INTO reports (id, user_id, box, datos, timestamp)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                box = VALUES(box),
                datos = VALUES(datos),
                timestamp = VALUES(timestamp)";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('ssiss', $id, $user_id, $box, $datos, $ts);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
