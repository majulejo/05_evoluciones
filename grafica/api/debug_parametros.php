<?php
// ===== API DE DEBUG PARA VERIFICAR PROBLEMAS =====
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Recopilar información de debug
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'php_version' => PHP_VERSION,
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    
    // Parámetros recibidos
    'get_params' => $_GET,
    'post_params' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    
    // Headers
    'headers' => getallheaders(),
    
    // Variables de servidor relevantes
    'server_vars' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
        'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? '',
        'CONTENT_LENGTH' => $_SERVER['CONTENT_LENGTH'] ?? 0
    ]
];

try {
    $host = 'localhost';
    $dbname = 'u724879249_data';
    $username = 'u724879249_data';
    $password = 'Farolill0.1';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $debug_info['database'] = [
        'connection' => 'SUCCESS',
        'host' => $host,
        'database' => $dbname
    ];
    
    // Verificar tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $debug_info['database']['tables'] = $tables;
    
    // Verificar tabla datos_oxigenacion específicamente
    if (in_array('datos_oxigenacion', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM datos_oxigenacion");
        $count = $stmt->fetch();
        $debug_info['database']['datos_oxigenacion_count'] = $count['total'];
        
        $stmt = $pdo->query("DESCRIBE datos_oxigenacion");
        $columns = $stmt->fetchAll();
        $debug_info['database']['datos_oxigenacion_structure'] = $columns;
        
        // Verificar datos de ejemplo para box específico
        if (isset($_GET['box'])) {
            $box = intval($_GET['box']);
            $stmt = $pdo->prepare("SELECT * FROM datos_oxigenacion WHERE box = ? LIMIT 5");
            $stmt->execute([$box]);
            $sample_data = $stmt->fetchAll();
            $debug_info['database']['sample_data_box_' . $box] = $sample_data;
        }
    } else {
        $debug_info['database']['datos_oxigenacion_exists'] = false;
    }
    
} catch (PDOException $e) {
    $debug_info['database'] = [
        'connection' => 'FAILED',
        'error' => $e->getMessage()
    ];
} catch (Exception $e) {
    $debug_info['database'] = [
        'connection' => 'ERROR',
        'error' => $e->getMessage()
    ];
}

// Verificar archivos de API
$api_files = [
    'obtener_oxigenacion.php',
    'guardar_oxigenacion.php',
    'obtener_constantes.php',
    'guardar_constantes.php'
];

$debug_info['api_files'] = [];
foreach ($api_files as $file) {
    $path = __DIR__ . '/' . $file;
    $debug_info['api_files'][$file] = [
        'exists' => file_exists($path),
        'readable' => file_exists($path) ? is_readable($path) : false,
        'size' => file_exists($path) ? filesize($path) : 0,
        'modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null
    ];
}

// Simular llamada a obtener_oxigenacion si se proporciona box
if (isset($_GET['test_box'])) {
    $test_box = intval($_GET['test_box']);
    
    try {
        // Simular la llamada
        $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/obtener_oxigenacion.php?box=' . $test_box;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' => 'Accept: application/json'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        $debug_info['test_api_call'] = [
            'url' => $url,
            'success' => $response !== false,
            'response' => $response ? json_decode($response, true) : null,
            'http_response_header' => $http_response_header ?? []
        ];
        
    } catch (Exception $e) {
        $debug_info['test_api_call'] = [
            'url' => $url ?? 'N/A',
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Retornar información de debug
echo json_encode([
    'success' => true,
    'message' => 'Información de debug recopilada',
    'debug' => $debug_info
], JSON_PRETTY_PRINT);
?>