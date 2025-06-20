<?php
// save_user_signature.php
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

// Leer datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
    exit();
}

$user_id = $_SESSION['user_id'];
$firma = $data['firma'] ?? '';

// Validar que el user_id coincida
if (isset($data['user_id']) && $data['user_id'] !== $user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Usuario no autorizado']);
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

    // Actualizar firma del usuario
    $sql = "UPDATE usuarios SET firma = :firma WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':firma' => $firma,
        ':user_id' => $user_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Firma guardada correctamente']);

} catch (PDOException $e) {
    error_log("Error en save_user_signature.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log("Error general en save_user_signature.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>