<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function manejarError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', 405);
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        manejarError('Datos JSON inválidos', 400);
    }

    // Validar campos requeridos
    if (!isset($data['numero_box']) || !isset($data['hora'])) {
        manejarError('Campos numero_box y hora son requeridos', 400);
    }

    $numero_box = (int)$data['numero_box'];
    $hora = $data['hora'];
    $hoja = $data['hoja'] ?? 1;

    $pdo = obtenerConexionBD();

    // Verificar que el paciente existe
    $stmt = $pdo->prepare("SELECT numero_box FROM pacientes WHERE numero_box = ?");
    $stmt->execute([$numero_box]);
    if (!$stmt->fetch()) {
        manejarError("No existe paciente en numero_box $numero_box", 404);
    }

    // Preparar campos para insertar/actualizar
    $campos = ['numero_box', 'hora', 'hoja'];
    $placeholders = ['?', '?', '?'];
    $valores = [$numero_box, $hora, $hoja];

    $camposOpcionales = [
        'p_neumo',
        'tipo_oxigenacion',
        'eva_escid',
        'sat_o2',
        'glucemia',
        'rass',
        'insulina',
        'oxigenacion'
    ];

    foreach ($camposOpcionales as $campo) {
        if (isset($data[$campo]) && $data[$campo] !== null && $data[$campo] !== '') {
            $campos[] = $campo;
            $placeholders[] = '?';
            $valores[] = $data[$campo];
        }
    }

    $camposStr = implode(', ', $campos);
    $placeholdersStr = implode(', ', $placeholders);

    $updateParts = [];
    foreach ($camposOpcionales as $campo) {
        if (isset($data[$campo]) && $data[$campo] !== null && $data[$campo] !== '') {
            $updateParts[] = "$campo = VALUES($campo)";
        }
    }
    $updateStr = implode(', ', $updateParts);

    $sql = "INSERT INTO datos_oxigenacion ($camposStr) VALUES ($placeholdersStr)";
    if (!empty($updateParts)) {
        $sql .= " ON DUPLICATE KEY UPDATE $updateStr";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);

    echo json_encode([
        'success' => true,
        'message' => 'Datos de oxigenación guardados correctamente'
    ]);

} catch (Exception $e) {
    error_log('Error en guardar_oxigenacion.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar datos de oxigenación: ' . $e->getMessage()
    ]);
}
?>