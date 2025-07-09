<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Use POST.');
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }

    if (!isset($data['numero_box']) || !isset($data['hora'])) {
        throw new Exception('Campos numero_box y hora son requeridos');
    }

    $numero_box = intval($data['numero_box']);
    $hora = $data['hora'];
    $hoja = isset($data['hoja']) ? intval($data['hoja']) : 1;

    require_once 'config.php';
    $pdo = obtenerConexionBD();

    $sql = "DELETE FROM constantes_vitales WHERE numero_box = :numero_box AND hora = :hora AND hoja = :hoja";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'numero_box' => $numero_box,
        'hora' => $hora,
        'hoja' => $hoja
    ]);

    $filasAfectadas = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => $filasAfectadas > 0 ? 'Constantes eliminadas correctamente' : 'No se encontraron constantes para eliminar',
        'numero_box' => $numero_box,
        'hora' => $hora,
        'hoja' => $hoja,
        'deleted_rows' => $filasAfectadas
    ]);

} catch (PDOException $e) {
    error_log("Error DB en eliminar_constantes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_type' => 'database'
    ]);
} catch (Exception $e) {
    error_log("Error en eliminar_constantes: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'validation'
    ]);
}
?>