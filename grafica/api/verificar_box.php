<?php
// archivo: api/verificar_box.php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $box = isset($_GET['box']) ? intval($_GET['box']) : 0;

    if ($box <= 0) {
        echo json_encode(['ocupado' => false, 'paciente' => null]);
        exit;
    }

    $pdo = obtenerConexionBD();
    
    // Verificar si hay paciente activo en el box
    $stmt = $pdo->prepare("
        SELECT nombre_completo, fecha_ingreso, box
        FROM pacientes 
        WHERE box = ? AND activo = 1
    ");
    
    $stmt->execute([$box]);
    $paciente = $stmt->fetch();

    if ($paciente) {
        // Obtener última actividad de constantes vitales
        $stmt = $pdo->prepare("
            SELECT MAX(fecha_registro) as ultima_actividad
            FROM constantes_vitales 
            WHERE box = ?
        ");
        $stmt->execute([$box]);
        $actividad = $stmt->fetch();

        echo json_encode([
            'ocupado' => true,
            'paciente' => $paciente,
            'ultima_actividad' => $actividad['ultima_actividad'] ?? $paciente['fecha_ingreso']
        ]);
    } else {
        echo json_encode([
            'ocupado' => false,
            'paciente' => null,
            'ultima_actividad' => null
        ]);
    }

} catch (Exception $e) {
    error_log('Error en verificar_box.php: ' . $e->getMessage());
    echo json_encode([
        'ocupado' => false, 
        'paciente' => null,
        'error' => $e->getMessage()
    ]);
}
?>

