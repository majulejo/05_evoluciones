<?php
// api/obtener_oxigenacion.php - NUEVA API PARA DATOS DE OXIGENACIÓN

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
    
    // ✅ VERIFICAR SI EXISTE LA TABLA datos_oxigenacion
    $checkTable = $pdo->query("SHOW TABLES LIKE 'datos_oxigenacion'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if (!$tableExists) {
        // Si no existe la tabla, crear respuesta vacía válida
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Tabla datos_oxigenacion no existe, creando estructura',
            'debug_info' => [
                'tabla_existe' => false,
                'box' => $box,
                'hoja' => $hoja
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // ✅ CONSULTAR DATOS DE OXIGENACIÓN
    $sql = "SELECT 
                do.id,
                do.box,
                do.hora,
                do.p_neumo,
                do.oxigenacion,
                do.eva_escid,
                do.rass,
                do.insulina,
                do.hoja,
                do.fecha_registro,
                -- Traer saturacion y glucemia de constantes vitales si existe
                cv.saturacion,
                cv.glucemia
            FROM datos_oxigenacion do
            LEFT JOIN constantes_vitales cv ON do.box = cv.box AND do.hora = cv.hora
            WHERE do.box = :box";
    
    // Añadir filtro de hoja si la columna existe
    $checkHojaColumn = $pdo->query("SHOW COLUMNS FROM datos_oxigenacion LIKE 'hoja'");
    if ($checkHojaColumn->rowCount() > 0) {
        $sql .= " AND do.hoja = :hoja";
    }
    
    $sql .= " ORDER BY do.hora ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':box', $box, PDO::PARAM_INT);
    
    if ($checkHojaColumn->rowCount() > 0) {
        $stmt->bindParam(':hoja', $hoja, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ✅ PROCESAR DATOS
    $datosFormateados = [];
    
    foreach ($datos as $registro) {
        $datosFormateados[] = [
            'id' => (int)$registro['id'],
            'box' => (int)$registro['box'],
            'hora' => $registro['hora'],
            'p_neumo' => $registro['p_neumo'] ? (int)$registro['p_neumo'] : null,
            'oxigenacion' => $registro['oxigenacion'],
            'eva_escid' => $registro['eva_escid'],
            'rass' => $registro['rass'] ? (int)$registro['rass'] : null,
            'insulina' => $registro['insulina'],
            'saturacion' => $registro['saturacion'] ? (int)$registro['saturacion'] : null,
            'glucemia' => $registro['glucemia'] ? (int)$registro['glucemia'] : null,
            'hoja' => isset($registro['hoja']) ? (int)$registro['hoja'] : $hoja,
            'fecha_registro' => $registro['fecha_registro']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $datosFormateados,
        'total_registros' => count($datosFormateados),
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
    error_log("Error DB en obtener_oxigenacion.php: " . $e->getMessage());
    
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
    error_log("Error en obtener_oxigenacion.php: " . $e->getMessage());
    
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