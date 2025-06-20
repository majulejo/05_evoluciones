<?php
// save_draft.php
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

// Validar campos requeridos
if (!isset($data['box']) || !isset($data['user_id']) || !isset($data['datos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campos requeridos faltantes']);
    exit();
}

$box = (int)$data['box'];
$user_id = $data['user_id'];
$datos = $data['datos'];

// Validar box
if ($box < 1 || $box > 12) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Número de box inválido']);
    exit();
}

// Validar que el user_id coincida con la sesión
if ($user_id !== $_SESSION['user_id']) {
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

    // Preparar datos para insertar/actualizar
    $datos_json = json_encode($datos);
    
    // Usar INSERT ... ON DUPLICATE KEY UPDATE para insertar o actualizar
    $sql = "INSERT INTO drafts (user_id, box, datos, timestamp) 
            VALUES (:user_id, :box, :datos, NOW()) 
            ON DUPLICATE KEY UPDATE 
            datos = VALUES(datos), 
            timestamp = NOW()";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':box' => $box,
        ':datos' => $datos_json
    ]);

    echo json_encode(['success' => true, 'message' => 'Draft guardado correctamente']);

} catch (PDOException $e) {
    error_log("Error en save_draft.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error general en save_draft.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>