<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$box = $_GET['box'] ?? null;
$dia = $_GET['dia'] ?? null;

if (!$box || !$dia) {
    echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
    exit;
}

try {
    // Calcular rango de fechas para el día específico
    $fechaIngreso = "2025-07-01 08:00:00"; // Obtener de BD
    $fechaDia = date('Y-m-d H:i:s', strtotime($fechaIngreso . ' +' . ($dia - 1) . ' days'));
    
    // Consultar constantes vitales del día
    $sqlConstantes = "SELECT * FROM constantes_vitales 
                      WHERE numero_box = ? 
                      AND fecha_registro >= ? 
                      AND fecha_registro < DATE_ADD(?, INTERVAL 1 DAY)
                      ORDER BY hora";
    
    // Consultar datos de oxigenación del día
    $sqlOxigenacion = "SELECT * FROM datos_oxigenacion 
                       WHERE numero_box = ? 
                       AND fecha >= ? 
                       AND fecha < DATE_ADD(?, INTERVAL 1 DAY)
                       ORDER BY hora";
    
    // Ejecutar consultas y formatear respuesta
    echo json_encode([
        'success' => true,
        'constantes' => [], // Datos formateados
        'oxigenacion' => [] // Datos formateados
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>