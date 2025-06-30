<?php
// obtener_oxigenacion.php - CREAR ESTE ARCHIVO NUEVO
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $box = $_GET['box'] ?? null;
    
    if (!$box) {
        throw new Exception("Número de box no especificado");
    }
    
    // Conectar a la base de datos
    $conexion = new mysqli("localhost", "u724879249_data", "Farolill0.1", "u724879249_data");
    
    
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8");
    
    // Obtener datos de oxigenación para el box
    $stmt = $conexion->prepare("
        SELECT hora, p_neumo, tipo_oxigenacion, eva_escid, rass, insulina, fecha_registro 
        FROM oxigenacion_datos 
        WHERE box = ? 
        ORDER BY fecha_registro DESC, hora ASC
    ");
    
    $stmt->bind_param("s", $box);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $oxigenacion = [];
    
    while ($fila = $resultado->fetch_assoc()) {
        $hora = $fila['hora'];
        
        // Solo incluir valores que no estén vacíos
        $datos = [];
        if (!empty($fila['p_neumo'])) $datos['pNeumo'] = (int)$fila['p_neumo'];
        if (!empty($fila['tipo_oxigenacion'])) $datos['tipoOxigenacion'] = $fila['tipo_oxigenacion'];
        if (!empty($fila['eva_escid'])) $datos['evaEscid'] = (int)$fila['eva_escid'];
        if (!empty($fila['rass'])) $datos['rass'] = (int)$fila['rass'];
        if (!empty($fila['insulina'])) $datos['insulina'] = (float)$fila['insulina'];
        
        $datos['fecha_registro'] = $fila['fecha_registro'];
        
        if (!empty($datos)) {
            $oxigenacion[$hora] = $datos;
        }
    }
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'oxigenacion' => $oxigenacion
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>