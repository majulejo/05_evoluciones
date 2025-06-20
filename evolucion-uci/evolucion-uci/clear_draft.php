<?php
// clear_draft.php
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
    $username = 'u724879249_jamarque06';
    $password = 'Farolill01.';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Establecer zona horaria en MySQL
    $pdo->exec("SET time_zone = '+01:00'");

    // Eliminar draft
    $delete_sql = "DELETE FROM drafts WHERE box = :box AND user_id = :user_id";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([
        ':box' => $box,
        ':user_id' => $user_id
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Draft eliminado correctamente',
        'box' => $box
    ]);

} catch (PDOException $e) {
    error_log("Error en clear_draft.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error general en clear_draft.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>