<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $box = $input['box'];
    
    // 1. Eliminar paciente activo del box
    $pacientesFile = 'data/pacientes_activos.json';
    if (file_exists($pacientesFile)) {
        $pacientes = json_decode(file_get_contents($pacientesFile), true);
        $pacientes = array_filter($pacientes, function($p) use ($box) {
            return $p['numero_box'] != $box;
        });
        file_put_contents($pacientesFile, json_encode(array_values($pacientes), JSON_PRETTY_PRINT));
    }

    // 2. Limpiar datos del box
    $archivosLimpiar = [
        "data/constantes_box_{$box}.json",
        "data/oxigenacion_box_{$box}.json"
    ];

    foreach ($archivosLimpiar as $archivo) {
        if (file_exists($archivo)) {
            unlink($archivo);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Alta procesada correctamente']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar alta: ' . $e->getMessage()]);
}
?>