<?php
// api/obtener_constantes.php - API PARA CONSTANTES VITALES

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ INCLUIR TU CONFIG.PHP
require_once '../config.php';

try {
    // ✅ OBTENER PARÁMETROS
    $box = $_GET['box'] ?? null;
    $hoja = $_GET['hoja'] ?? 1;
    
    if (!$box || !is_numeric($box)) {
        throw new Exception("Box inválido: " . $box);
    }
    
    $box = (int)$box;
    $hoja = (int)$hoja;
    
    // ✅ USAR TU FUNCIÓN DE CONEXIÓN
    $pdo = obtenerConexionBD();
    
    // ✅ VERIFICAR SI EXISTE LA TABLA constantes_vitales
    $checkTable = $pdo->query("SHOW TABLES LIKE 'constantes_vitales'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if (!$tableExists) {
        // Si no existe la tabla, respuesta vacía válida
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Tabla constantes_vitales no existe',
            'debug_info' => [
                'tabla_existe' => false,
                'box' => $box,
                'hoja' => $hoja
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // ✅ CONSULTAR CONSTANTES VITALES
    $sql = "SELECT 
                cv.id,
                cv.box,
                cv.hora,
                cv.fr,
                cv.temperatura,
                cv.fc,
                cv.ta_sistolica,
                cv.ta_diastolica,
                cv.saturacion,
                cv.glucemia,
                cv.hoja,
                cv.fecha_registro
            FROM constantes_vitales cv
            WHERE cv.box = :box";
    
    // Añadir filtro de hoja si la columna existe
    $checkHojaColumn = $pdo->query("SHOW COLUMNS FROM constantes_vitales LIKE 'hoja'");
    if ($checkHojaColumn->rowCount() > 0) {
        $sql .= " AND cv.hoja = :hoja";
    }
    
    $sql .= " ORDER BY cv.hora ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':box', $box, PDO::PARAM_INT);
    
    if ($checkHojaColumn->rowCount() > 0) {
        $stmt->bindParam(':hoja', $hoja, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $constantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ✅ PROCESAR DATOS
    $constantesFormateadas = [];
    
    foreach ($constantes as $registro) {
        $constantesFormateadas[] = [
            'id' => (int)$registro['id'],
            'box' => (int)$registro['box'],
            'hora' => $registro['hora'],
            'fr' => $registro['fr'] ? (int)$registro['fr'] : null,
            'temperatura' => $registro['temperatura'] ? (float)$registro['temperatura'] : null,
            'fc' => $registro['fc'] ? (int)$registro['fc'] : null,
            'ta_sistolica' => $registro['ta_sistolica'] ? (int)$registro['ta_sistolica'] : null,
            'ta_diastolica' => $registro['ta_diastolica'] ? (int)$registro['ta_diastolica'] : null,
            'saturacion' => $registro['saturacion'] ? (int)$registro['saturacion'] : null,
            'glucemia' => $registro['glucemia'] ? (int)$registro['glucemia'] : null,
            'hoja' => isset($registro['hoja']) ? (int)$registro['hoja'] : $hoja,
            'fecha_registro' => $registro['fecha_registro']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $constantesFormateadas,
        'total_registros' => count($constantesFormateadas),
        'debug_info' => [
            'tabla_existe' => $tableExists,
            'tiene_columna_hoja' => $checkHojaColumn->rowCount() > 0,
            'box' => $box,
            'hoja' => $hoja,
            'consulta_sql' => $sql
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Error DB en obtener_constantes.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_type' => 'database',
        'debug_info' => [
            'box' => $box ?? 'no definido',
            'hoja' => $hoja ?? 'no definido'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en obtener_constantes.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'validation',
        'debug_info' => [
            'parametros' => $_GET
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>