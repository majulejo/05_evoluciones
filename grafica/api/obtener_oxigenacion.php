<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Configuración de base de datos
$host = 'localhost';
$dbname = 'u724879249_data';
$username = 'u724879249_data'; // Ajustar según tu configuración
$password = 'Farolill0.1'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $box = $_GET['box'] ?? null;
    
    if (!$box) {
        echo json_encode(['success' => false, 'message' => 'Parámetro box requerido']);
        exit;
    }
    
    try {
        $sql = "SELECT 
                    hora,
                    p_neumo,
                    oxigenacion,
                    sat_o2,
                    eva_escid,
                    glucemia,
                    insulina,
                    created_at,
                    updated_at
                FROM oxigenacion_datos 
                WHERE numero_box = ? 
                ORDER BY hora ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$box]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en consulta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>