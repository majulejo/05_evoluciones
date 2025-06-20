<?php
// save_report.php
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
if (!isset($data['id']) || !isset($data['user_id']) || !isset($data['box']) || !isset($data['datos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campos requeridos faltantes']);
    exit();
}

$informe_id = $data['id'];
$user_id = $data['user_id'];
$box = (int)$data['box'];
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

    // Preparar fecha y hora actual en Madrid
    $fecha_actual = date('Y-m-d');
    
    // Insertar nuevo informe usando los campos individuales de tu tabla
    $sql = "INSERT INTO informes (
        id, box, fecha, timestamp, user_id, 
        neurologico, cardiovascular, respiratorio, renal, gastrointestinal, 
        nutricional, termorregulacion, piel, otros, especial, datos
    ) VALUES (
        :id, :box, :fecha, NOW(), :user_id,
        :neurologico, :cardiovascular, :respiratorio, :renal, :gastrointestinal,
        :nutricional, :termorregulacion, :piel, :otros, :especial, :datos_json
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $informe_id,
        ':box' => $box,
        ':fecha' => $fecha_actual,
        ':user_id' => $user_id,
        ':neurologico' => $datos['neurologico'] ?? '',
        ':cardiovascular' => $datos['cardiovascular'] ?? '',
        ':respiratorio' => $datos['respiratorio'] ?? '',
        ':renal' => $datos['renal'] ?? '',
        ':gastrointestinal' => $datos['gastrointestinal'] ?? '',
        ':nutricional' => $datos['nutricional'] ?? '',
        ':termorregulacion' => $datos['termorregulacion'] ?? '',
        ':piel' => $datos['piel'] ?? '',
        ':otros' => $datos['otros'] ?? '',
        ':especial' => $datos['especial'] ?? '',
        ':datos_json' => json_encode($datos)
    ]);

    // Limpiar draft después de guardar el informe
    $delete_draft_sql = "DELETE FROM drafts WHERE user_id = :user_id AND box = :box";
    $delete_stmt = $pdo->prepare($delete_draft_sql);
    $delete_stmt->execute([
        ':user_id' => $user_id,
        ':box' => $box
    ]);

    // Obtener hora actual para respuesta
    $hora_actual = date('H:i:s');

    echo json_encode([
        'success' => true, 
        'message' => 'Informe guardado correctamente',
        'id' => $informe_id,
        'fecha' => $fecha_actual,
        'hora' => $hora_actual
    ]);

} catch (PDOException $e) {
    error_log("Error en save_report.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error general en save_report.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>