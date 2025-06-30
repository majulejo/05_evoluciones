<?php
// obtener_constantes.php - Obtener constantes vitales de un paciente

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Configuración de base de datos directa
$host = 'localhost';
$dbname = 'u724879249_data';
$username = 'u724879249_data';
$password = 'Farolill0.1';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit;
}

// Obtener número de box
$numeroBox = intval($_GET['box'] ?? 0);

// Validar número de box
if ($numeroBox < 1 || $numeroBox > 12) {
    echo json_encode([
        'success' => false,
        'error' => 'Número de box inválido'
    ]);
    exit;
}

try {
    // Obtener fecha de hoja actual (fecha del turno hospitalario)
    $fechaHoja = date('Y-m-d');
    $horaActual = intval(date('H'));
    if ($horaActual < 8) {
        // Si es antes de las 8 AM, pertenece al día anterior
        $fechaHoja = date('Y-m-d', strtotime('-1 day'));
    }
    
    // Obtener constantes vitales del día actual
    $stmt = $pdo->prepare("
        SELECT 
            hora,
            fr,
            temperatura,
            fc,
            ta_sistolica,
            ta_diastolica,
            sat_o2,
            glucemia,
            fecha_registro
        FROM constantes_vitales 
        WHERE numero_box = ? AND fecha_hoja = ?
        ORDER BY hora ASC
    ");
    
    $stmt->execute([$numeroBox, $fechaHoja]);
    $resultados = $stmt->fetchAll();
    
    // Formatear datos para el frontend
    $constantes = [];
    
    foreach ($resultados as $row) {
        $hora = $row['hora'];
        $constantes[$hora] = [];
        
        // Añadir cada constante si no es null
        if ($row['fr'] !== null) $constantes[$hora]['FR'] = intval($row['fr']);
        if ($row['temperatura'] !== null) $constantes[$hora]['temperatura'] = floatval($row['temperatura']);
        if ($row['fc'] !== null) $constantes[$hora]['FC'] = intval($row['fc']);
        if ($row['ta_sistolica'] !== null) $constantes[$hora]['taSistolica'] = intval($row['ta_sistolica']);
        if ($row['ta_diastolica'] !== null) $constantes[$hora]['taDiastolica'] = intval($row['ta_diastolica']);
        if ($row['sat_o2'] !== null) $constantes[$hora]['satO2'] = intval($row['sat_o2']);
        if ($row['glucemia'] !== null) $constantes[$hora]['glucemia'] = intval($row['glucemia']);
        
        $constantes[$hora]['fecha_registro'] = $row['fecha_registro'];
        
        // Si no hay constantes, eliminar la entrada
        if (count($constantes[$hora]) <= 1) { // Solo fecha_registro
            unset($constantes[$hora]);
        }
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'constantes' => $constantes,
        'fecha_hoja' => $fechaHoja,
        'total_registros' => count($resultados),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Error al obtener constantes: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener constantes vitales',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error general al obtener constantes: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>