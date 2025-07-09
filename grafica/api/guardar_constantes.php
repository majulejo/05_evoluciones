<?php
require_once 'config.php';

function manejarError($mensaje, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

function responderJSON($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
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

    if (!isset($data['numero_box']) || !isset($data['hora'])) {
        manejarError('Campos numero_box y hora son requeridos', 400);
    }

    $numero_box = (int)$data['numero_box'];
    $hora = $data['hora'];
    $hoja = $data['hoja'] ?? 1;

    $pdo = obtenerConexionBD();

    $stmt = $pdo->prepare("SELECT numero_box FROM pacientes WHERE numero_box = ?");
    $stmt->execute([$numero_box]);
    if (!$stmt->fetch()) {
        manejarError("No existe paciente en numero_box $numero_box", 404);
    }

    $campos = [];
    $valores = [$numero_box, $hora, $hoja];
    $placeholders = ['?', '?', '?'];

    $camposOpcionales = [
        'fr' => 'fr',
        'temperatura' => 'temperatura',
        'fc' => 'fc',
        'ta_sistolica' => 'ta_sistolica',
        'ta_diastolica' => 'ta_diastolica',
        'sat_o2' => 'sat_o2',
        'glucemia' => 'glucemia'
    ];

    foreach ($camposOpcionales as $key => $campo) {
        if (isset($data[$key]) && $data[$key] !== null && $data[$key] !== '') {
            $campos[] = $campo;
            $valores[] = $data[$key];
            $placeholders[] = '?';
        }
    }

    $camposStr = implode(', ', array_merge(['numero_box', 'hora', 'hoja'], $campos));
    $placeholdersStr = implode(', ', $placeholders);

    $updateParts = [];
    foreach ($campos as $campo) {
        $updateParts[] = "$campo=VALUES($campo)";
    }
    $updateStr = implode(', ', $updateParts);

    $sql = "INSERT INTO constantes_vitales ($camposStr) VALUES ($placeholdersStr)";
    if (!empty($updateParts)) {
        $sql .= " ON DUPLICATE KEY UPDATE $updateStr";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);

    responderJSON([
        'success' => true,
        'message' => 'Constantes vitales guardadas correctamente'
    ]);

} catch (Exception $e) {
    error_log('Error en guardar_constantes.php: ' . $e->getMessage());
    manejarError('Error al guardar constantes: ' . $e->getMessage(), 500);
}
?>