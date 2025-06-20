<?php
// delete_reports.php
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
if (!isset($data['id']) || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de informe requerido']);
    exit();
}

$informe_id = $data['id'];
$user_id = $_SESSION['user_id'];

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

    // Verificar que el informe existe y pertenece al usuario
    $check_sql = "SELECT id, box FROM informes WHERE id = :id AND user_id = :user_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([
        ':id' => $informe_id,
        ':user_id' => $user_id
    ]);

    $informe = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$informe) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Informe no encontrado o no autorizado']);
        exit();
    }

    // Eliminar el informe
    $delete_sql = "DELETE FROM informes WHERE id = :id AND user_id = :user_id";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([
        ':id' => $informe_id,
        ':user_id' => $user_id
    ]);

    if ($delete_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Informe eliminado correctamente',
            'deleted_id' => $informe_id,
            'box' => $informe['box']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el informe']);
    }

} catch (PDOException $e) {
    error_log("Error en delete_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error general en delete_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>