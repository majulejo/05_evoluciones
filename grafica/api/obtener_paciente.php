<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

try {
    $numero_box = $_GET['numero_box'] ?? null;

    if (!$numero_box || !is_numeric($numero_box)) {
        throw new Exception("numero_box inválido: " . $numero_box);
    }
    $numero_box = (int)$numero_box;

    $pdo = obtenerConexionBD();

    $sql = "SELECT 
                p.id,
                p.box,
                p.numero_box,
                p.nombre_completo,
                p.edad,
                p.peso,
                p.numero_historia,
                p.numero_hoja,
                p.fecha_ingreso,
                p.fecha_alta,
                p.estado,
                p.fecha_creacion
            FROM pacientes p 
            WHERE p.numero_box = :numero_box 
            AND p.estado = 'activo' 
            AND (p.fecha_alta IS NULL OR p.fecha_alta = '0000-00-00 00:00:00')
            ORDER BY p.fecha_ingreso DESC 
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':numero_box', $numero_box, PDO::PARAM_INT);
    $stmt->execute();

    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($paciente) {
        $paciente['id'] = (int)$paciente['id'];
        $paciente['box'] = (int)$paciente['box'];
        $paciente['numero_box'] = (int)$paciente['numero_box'];
        $paciente['edad'] = (int)$paciente['edad'];
        $paciente['peso'] = $paciente['peso'] ? (float)$paciente['peso'] : null;
        $paciente['activo'] = ($paciente['estado'] === 'activo');
        if ($paciente['fecha_ingreso']) {
            $fecha = new DateTime($paciente['fecha_ingreso']);
            $paciente['fecha_ingreso'] = $fecha->format('Y-m-d H:i:s');
        }
        echo json_encode([
            'success' => true,
            'data' => $paciente,
            'message' => 'Paciente encontrado',
            'debug_info' => [
                'numero_box_consultado' => $numero_box,
                'estado_paciente' => $paciente['estado'],
                'fecha_ingreso' => $paciente['fecha_ingreso']
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No hay paciente activo en este box'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener paciente: ' . $e->getMessage()
    ]);
}
?>