<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $pdo = obtenerConexionBD();
    
    // Obtener información de las columnas de la tabla oxigenacion
    $stmt = $pdo->prepare("DESCRIBE datos_oxigenacion");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'columnas' => $columnas,
        'message' => 'Estructura de la tabla datos_oxigenacion'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>