<?php
// obtener_paciente.php - Obtener datos completos de un paciente

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
    // Obtener datos completos del paciente
    $stmt = $pdo->prepare("
        SELECT 
            numero_box,
            nombre_paciente as nombre_completo,
            edad,
            peso,
            numero_historia,
            fecha_ingreso,
            fecha_modificacion
        FROM pacientes_boxes 
        WHERE numero_box = ? AND activo = TRUE
    ");
    
    $stmt->execute([$numeroBox]);
    $paciente = $stmt->fetch();
    
    if ($paciente) {
        // Verificar si hay paciente ingresado
        if (!empty($paciente['nombre_completo'])) {
            echo json_encode([
                'success' => true,
                'paciente' => $paciente,
                'box_ocupado' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'paciente' => null,
                'box_ocupado' => false,
                'message' => 'Box disponible para nuevo ingreso',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Box no encontrado',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

} catch (PDOException $e) {
    error_log("Error al obtener paciente: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener datos del paciente',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error general al obtener paciente: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>