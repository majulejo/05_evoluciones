<?php
// cargar_pacientes.php - Cargar datos de pacientes desde la base de datos

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Incluir configuración de base de datos
require_once 'config_simple.php';

try {
    // Consultar todos los boxes
    $stmt = $pdo->prepare("
        SELECT numero_box, nombre_paciente, fecha_ingreso 
        FROM pacientes_boxes 
        WHERE activo = TRUE 
        ORDER BY numero_box ASC
    ");
    
    $stmt->execute();
    $resultados = $stmt->fetchAll();
    
    // Formatear datos para el frontend
    $pacientes = [];
    
    // Inicializar todos los boxes como vacíos
    for ($i = 1; $i <= 12; $i++) {
        $pacientes[$i] = null;
    }
    
    // Llenar con datos de la base de datos
    foreach ($resultados as $row) {
        $numeroBox = intval($row['numero_box']);
        if ($numeroBox >= 1 && $numeroBox <= 12) {
            $pacientes[$numeroBox] = !empty($row['nombre_paciente']) ? $row['nombre_paciente'] : null;
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'pacientes' => $pacientes,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    // Error en la base de datos
    error_log("Error al cargar pacientes: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar datos de pacientes',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Otros errores
    error_log("Error general al cargar pacientes: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>