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

    $checkTable = $pdo->query("SHOW TABLES LIKE 'datos_oxigenacion'");
    $tableExists = $checkTable->rowCount() > 0;

    if (!$tableExists) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Tabla datos_oxigenacion no existe, creando estructura',
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
                do.id,
                do.numero_box,
                do.box,
                do.hora,
                do.p_neumo,
                do.tipo_oxigenacion,
                do.eva_escid,
                do.rass,
                do.insulina,
                do.hoja,
                do.fecha_registro,
                do.oxigenacion,
                do.glucemia,
                do.sat_o2
            FROM datos_oxigenacion do
            WHERE do.numero_box = :numero_box";
    $checkHojaColumn = $pdo->query("SHOW COLUMNS FROM datos_oxigenacion LIKE 'hoja'");
    if ($checkHojaColumn->rowCount() > 0) {
        $sql .= " AND do.hoja = :hoja";
    }
    $sql .= " ORDER BY do.hora ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':numero_box', $numero_box, PDO::PARAM_INT);
    if ($checkHojaColumn->rowCount() > 0) {
        $stmt->bindParam(':hoja', $hoja, PDO::PARAM_INT);
    }

    $stmt->execute();
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $datos,
        'message' => 'Datos de oxigenación obtenidos',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener oxigenación: ' . $e->getMessage()
    ]);
}
?>