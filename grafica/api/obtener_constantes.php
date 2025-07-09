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
    $hoja = $_GET['hoja'] ?? 1;

    if (!$numero_box || !is_numeric($numero_box)) {
        throw new Exception("numero_box inválido: " . $numero_box);
    }

    $numero_box = (int)$numero_box;
    $hoja = (int)$hoja;

    $pdo = obtenerConexionBD();

    $checkTable = $pdo->query("SHOW TABLES LIKE 'constantes_vitales'");
    $tableExists = $checkTable->rowCount() > 0;

    if (!$tableExists) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Tabla constantes_vitales no existe',
            'debug_info' => [
                'tabla_existe' => false,
                'numero_box' => $numero_box,
                'hoja' => $hoja
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $sql = "SELECT 
                cv.id,
                cv.numero_box,
                cv.hora,
                cv.fr,
                cv.temperatura,
                cv.fc,
                cv.ta_sistolica,
                cv.ta_diastolica,
                cv.sat_o2,
                cv.glucemia,
                cv.hoja,
                cv.fecha_registro
            FROM constantes_vitales cv
            WHERE cv.numero_box = :numero_box";
    $checkHojaColumn = $pdo->query("SHOW COLUMNS FROM constantes_vitales LIKE 'hoja'");
    if ($checkHojaColumn->rowCount() > 0) {
        $sql .= " AND cv.hoja = :hoja";
    }

    $sql .= " ORDER BY cv.hora ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':numero_box', $numero_box, PDO::PARAM_INT);

    if ($checkHojaColumn->rowCount() > 0) {
        $stmt->bindParam(':hoja', $hoja, PDO::PARAM_INT);
    }

    $stmt->execute();
    $constantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $constantesFormateadas = [];
    foreach ($constantes as $registro) {
        $constantesFormateadas[] = [
            'id' => (int)$registro['id'],
            'numero_box' => (int)$registro['numero_box'],
            'hora' => $registro['hora'],
            'fr' => $registro['fr'] !== null ? (int)$registro['fr'] : null,
            'temperatura' => $registro['temperatura'] !== null ? (float)$registro['temperatura'] : null,
            'fc' => $registro['fc'] !== null ? (int)$registro['fc'] : null,
            'ta_sistolica' => $registro['ta_sistolica'] !== null ? (int)$registro['ta_sistolica'] : null,
            'ta_diastolica' => $registro['ta_diastolica'] !== null ? (int)$registro['ta_diastolica'] : null,
            'sat_o2' => $registro['sat_o2'] !== null ? (int)$registro['sat_o2'] : null,
            'glucemia' => $registro['glucemia'] !== null ? (int)$registro['glucemia'] : null,
            'hoja' => (int)$registro['hoja'],
            'fecha_registro' => $registro['fecha_registro']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $constantesFormateadas,
        'message' => 'Constantes vitales obtenidas',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener constantes: ' . $e->getMessage()
    ]);
}
?>