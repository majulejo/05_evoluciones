<?php
// justo después de abrir PHP
date_default_timezone_set('Europe/Madrid');

/*-----------------------------------------------------------------
 * get_report.php – devuelve un informe completo
 *    GET id=<uuid>  (obligatorio)
 *----------------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 0);

try {
    if (empty($_GET['id'])) {
        throw new Exception('Parámetro id requerido');
    }

    $id = $_GET['id'];                 // string uuid

    $stmt = $db->prepare(
        "SELECT id, user_id, box, datos
           FROM reports
          WHERE id = ?"
    );
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) throw new Exception('Informe no encontrado');

    echo json_encode([
        'success' => true,
        'id'      => $row['id'],
        'user_id' => $row['user_id'],
        'box'     => (int)$row['box'],
        'datos'   => json_decode($row['datos'], true, 512, JSON_THROW_ON_ERROR)
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
