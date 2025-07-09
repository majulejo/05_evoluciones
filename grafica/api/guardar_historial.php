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
    // Crear directorio si no existe
    if (!is_dir('data')) {
        mkdir('data', 0755, true);
    }
    
    $historialFile = 'data/historial.json';
    $historial = file_exists($historialFile) ? json_decode(file_get_contents($historialFile), true) : [];

    // Crear registro de historial
    $registro = [
        'id' => uniqid('hist_', true),
        'fecha_alta' => $input['fecha_alta'],
        'nombre_completo' => $input['paciente']['nombre_completo'],
        'edad' => $input['paciente']['edad'],
        'peso' => $input['paciente']['peso'],
        'numero_historia' => $input['paciente']['numero_historia'],
        'box' => $input['box'],
        'fecha_ingreso' => $input['paciente']['fecha_ingreso'],
        'tiempo_estancia' => $input['tiempo_estancia'],
        'constantes_vitales' => $input['constantes_vitales'],
        'datos_oxigenacion' => $input['datos_oxigenacion'],
        'resumen_estancia' => $input['resumen_estancia'],
        'total_registros_constantes' => count($input['constantes_vitales']),
        'total_registros_oxigenacion' => count($input['datos_oxigenacion'])
    ];

    // Añadir al historial
    $historial[] = $registro;

    // Guardar archivo
    file_put_contents($historialFile, json_encode($historial, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'message' => 'Historial guardado correctamente']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar historial: ' . $e->getMessage()]);
}
?>