<?php
// delete_box_reports.php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
date_default_timezone_set('Europe/Madrid');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Leer datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit();
}

// Validar campo requerido
if (!isset($data['box']) || !is_numeric($data['box'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Número de box requerido']);
    exit();
}

$box = (int)$data['box'];
$user_id = $_SESSION['user_id'];

// Validar box
if ($box < 1 || $box > 12) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Número de box inválido']);
    exit();
}

try {
    // Conexión a la base de datos
    $host = 'localhost';
    $dbname = 'u724879249_evolucion_uci';
    $username = 'u724879249_jamarquez06';
    $password = 'Farolill01.';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Establecer zona horaria en MySQL
    $pdo->exec("SET time_zone = '+01:00'");

    // Contar cuántos informes se van a eliminar
    $count_sql = "SELECT COUNT(*) as total FROM informes WHERE box = :box AND user_id = :user_id";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([
        ':box' => $box,
        ':user_id' => $user_id
    ]);
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_informes = $count_result['total'];

    if ($total_informes == 0) {
        echo json_encode([
            'success' => true, 
            'message' => "No hay informes para eliminar en el Box $box"
        ]);
        exit();
    }

    // Eliminar todos los informes del box para este usuario
    $delete_sql = "DELETE FROM informes WHERE box = :box AND user_id = :user_id";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([
        ':box' => $box,
        ':user_id' => $user_id
    ]);

    $deleted_count = $delete_stmt->rowCount();

    if ($deleted_count > 0) {
        echo json_encode([
            'success' => true, 
            'message' => "Se eliminaron $deleted_count informes del Box $box",
            'deleted_count' => $deleted_count,
            'box' => $box
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudieron eliminar los informes']);
    }

} catch (PDOException $e) {
    error_log("Error en delete_box_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error general en delete_box_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>