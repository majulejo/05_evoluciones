<?php
/*-----------------------------------------------------------------
 * delete_box_reports.php – borra todos los informes de un box
 *  POST JSON: { user_id, box }
 *----------------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 0);

try {
    $in = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

    foreach (['user_id','box'] as $k) {
        if (empty($in[$k])) throw new Exception("Campo '$k' requerido");
    }

    $user_id = $in['user_id'];        // string
    $box     = (int)$in['box'];       // entero

    $stmt = $db->prepare(
        "DELETE FROM reports
          WHERE user_id = ? AND box = ?"
    );
    $stmt->bind_param('si', $user_id, $box);
    $stmt->execute();

    echo json_encode(['success'     => true,
                      'deletedRows' => $stmt->affected_rows]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
