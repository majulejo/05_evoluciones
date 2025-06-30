<?php
// gestionar_pacientes.php - Gestionar ingresos y altas de pacientes

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuración de la base de datos
$servername = "localhost";
$username = "u724879249_data";
$password = "Farolill0.1";
$dbname = "u724879249_data";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Error de conexión a la base de datos',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch($accion) {
    case 'obtener_paciente':
        obtenerPaciente($pdo);
        break;
    
    case 'crear_paciente':
        crearPaciente($pdo);
        break;
    
    case 'actualizar_datos':
        actualizarDatosPaciente($pdo);
        break;
    
    case 'alta_paciente':
        darAltaPaciente($pdo);
        break;
    
    case 'obtener_pacientes_activos':
        obtenerPacientesActivos($pdo);
        break;
        
    case 'limpiar_base_datos':
        limpiarBaseDatos($pdo);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

function obtenerPaciente($pdo) {
    $box = $_GET['box'] ?? '';
    
    if (empty($box)) {
        echo json_encode(['success' => false, 'error' => 'Número de box requerido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                nombre_completo,
                edad,
                peso,
                numero_historia,
                fecha_ingreso,
                estado,
                fecha_creacion
            FROM pacientes 
            WHERE numero_box = ? AND estado = 'activo'
        ");
        $stmt->execute([$box]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paciente) {
            echo json_encode([
                'success' => true,
                'paciente' => $paciente
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No hay paciente en este box'
            ]);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al obtener paciente: ' . $e->getMessage()]);
    }
}

function crearPaciente($pdo) {
    $box = $_POST['box'] ?? '';
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $edad = $_POST['edad'] ?? null;
    $peso = $_POST['peso'] ?? null;
    $historia = trim($_POST['numero_historia'] ?? '');
    
    // Validaciones
    if (empty($box) || empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'Box y nombre son obligatorios']);
        return;
    }
    
    if ($edad !== null && ($edad < 0 || $edad > 120)) {
        echo json_encode(['success' => false, 'error' => 'Edad debe estar entre 0 y 120 años']);
        return;
    }
    
    if ($peso !== null && ($peso < 0 || $peso > 500)) {
        echo json_encode(['success' => false, 'error' => 'Peso debe estar entre 0 y 500 kg']);
        return;
    }
    
    try {
        // Verificar si ya hay un paciente activo en este box
        $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE numero_box = ? AND estado = 'activo'");
        $stmt->execute([$box]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Ya hay un paciente activo en este box']);
            return;
        }
        
        // Crear nuevo paciente
        $stmt = $pdo->prepare("
            INSERT INTO pacientes (
                numero_box, 
                nombre_completo, 
                edad, 
                peso, 
                numero_historia, 
                fecha_ingreso, 
                estado, 
                fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, NOW(), 'activo', NOW())
        ");
        
        $stmt->execute([
            $box,
            $nombre,
            $edad,
            $peso,
            $historia
        ]);
        
        // Obtener el paciente recién creado
        $pacienteId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("
            SELECT 
                id,
                nombre_completo,
                edad,
                peso,
                numero_historia,
                fecha_ingreso,
                estado,
                fecha_creacion
            FROM pacientes WHERE id = ?
        ");
        $stmt->execute([$pacienteId]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Paciente creado correctamente',
            'paciente' => $paciente
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al crear paciente: ' . $e->getMessage()]);
    }
}

function actualizarDatosPaciente($pdo) {
    $box = $_POST['box'] ?? '';
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $edad = $_POST['edad'] ?? null;
    $peso = $_POST['peso'] ?? null;
    $historia = trim($_POST['numero_historia'] ?? '');
    
    // Validaciones
    if (empty($box) || empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'Box y nombre son obligatorios']);
        return;
    }
    
    if ($edad !== null && ($edad < 0 || $edad > 120)) {
        echo json_encode(['success' => false, 'error' => 'Edad debe estar entre 0 y 120 años']);
        return;
    }
    
    if ($peso !== null && ($peso < 0 || $peso > 500)) {
        echo json_encode(['success' => false, 'error' => 'Peso debe estar entre 0 y 500 kg']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE pacientes 
            SET nombre_completo = ?, 
                edad = ?, 
                peso = ?, 
                numero_historia = ?
            WHERE numero_box = ? AND estado = 'activo'
        ");
        
        $resultado = $stmt->execute([
            $nombre,
            $edad,
            $peso,
            $historia,
            $box
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Datos actualizados correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No se encontró paciente activo en este box'
            ]);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()]);
    }
}

function darAltaPaciente($pdo) {
    $box = $_POST['box'] ?? '';
    
    if (empty($box)) {
        echo json_encode(['success' => false, 'error' => 'Número de box requerido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE pacientes 
            SET estado = 'alta', 
                fecha_alta = NOW() 
            WHERE numero_box = ? AND estado = 'activo'
        ");
        
        $stmt->execute([$box]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Paciente dado de alta correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No se encontró paciente activo en este box'
            ]);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al dar de alta: ' . $e->getMessage()]);
    }
}

function obtenerPacientesActivos($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                numero_box,
                nombre_completo,
                edad,
                fecha_ingreso,
                TIMESTAMPDIFF(HOUR, fecha_ingreso, NOW()) as horas_ingreso
            FROM pacientes 
            WHERE estado = 'activo'
            ORDER BY numero_box ASC
        ");
        $stmt->execute();
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'pacientes' => $pacientes
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al obtener pacientes: ' . $e->getMessage()]);
    }
}

function limpiarBaseDatos($pdo) {
    // Esta función solo debe usarse en desarrollo
    try {
        // Eliminar todos los registros de pacientes
        $stmt = $pdo->prepare("DELETE FROM pacientes");
        $stmt->execute();
        
        // Reiniciar el AUTO_INCREMENT
        $stmt = $pdo->prepare("ALTER TABLE pacientes AUTO_INCREMENT = 1");
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Base de datos limpiada correctamente'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al limpiar base de datos: ' . $e->getMessage()]);
    }
}
?>