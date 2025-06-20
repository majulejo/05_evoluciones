<?php
// get_draft.php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
date_default_timezone_set('Europe/Madrid');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Validar parámetro box
if (!isset($_GET['box']) || !is_numeric($_GET['box'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetro box requerido']);
    exit();
}

$box = (int)$_GET['box'];
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

    // Buscar draft
    $sql = "SELECT datos FROM drafts WHERE user_id = :user_id AND box = :box";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':box' => $box
    ]);

    $draft = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($draft) {
        $datos = json_decode($draft['datos'], true);
        echo json_encode(['success' => true, 'datos' => $datos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay draft guardado']);
    }

} catch (PDOException $e) {
    error_log("Error en get_draft.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log("Error general en get_draft.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>