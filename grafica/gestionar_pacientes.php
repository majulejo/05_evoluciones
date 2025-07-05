<?php
require_once 'config.php';

// Headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = obtenerConexionBD();
    
    // Manejar solicitudes GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $accion = $_GET['accion'] ?? '';
        
        switch($accion) {
            case 'obtener_paciente':
                if (empty($_GET['box'])) {
                    echo json_encode(['success' => false, 'error' => 'Box no especificado']);
                    exit;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE numero_box = ? AND estado = 'activo' ORDER BY fecha_ingreso DESC LIMIT 1");
                $stmt->execute([$_GET['box']]);
                $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($paciente) {
                    echo json_encode(['success' => true, 'paciente' => $paciente]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'No se encontraron datos del paciente']);
                }
                break;
                
            case 'obtener_pacientes_activos':
                // ✅ FUNCIÓN PRINCIPAL PARA EL INDEX.HTML
                $stmt = $pdo->prepare("SELECT numero_box, nombre_completo FROM pacientes WHERE estado = 'activo' ORDER BY numero_box ASC");
                $stmt->execute();
                $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'pacientes' => $pacientes,
                    'count' => count($pacientes)
                ]);
                break;
                
            case 'verificar_estado_box':
                $box = $_GET['box'] ?? null;
                if (!$box) {
                    echo json_encode(['success' => false, 'error' => 'Box no especificado']);
                    exit;
                }
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as ocupado FROM pacientes WHERE numero_box = ? AND estado = 'activo'");
                $stmt->execute([$box]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'ocupado' => $result['ocupado'] > 0,
                    'box' => $box
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción GET no válida']);
                break;
        }
        exit;
    }
    
    // Manejar solicitudes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['accion'])) {
            echo json_encode(['success' => false, 'error' => 'Acción no especificada']);
            exit;
        }
        
        $accion = $_POST['accion'];
        
        switch ($accion) {
            case 'crear_paciente':
                // Validación de campos obligatorios
                if (
                    empty($_POST['nombre_completo']) ||
                    empty($_POST['edad']) ||
                    empty($_POST['numero_historia']) ||
                    empty($_POST['box'])
                ) {
                    echo json_encode(['success' => false, 'error' => 'Faltan campos obligatorios']);
                    exit;
                }
                
                // Verificar que el box no esté ocupado
                $stmtVerificar = $pdo->prepare("SELECT COUNT(*) as ocupado FROM pacientes WHERE numero_box = ? AND estado = 'activo'");
                $stmtVerificar->execute([$_POST['box']]);
                $verificacion = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
                
                if ($verificacion['ocupado'] > 0) {
                    echo json_encode(['success' => false, 'error' => 'El box ya está ocupado']);
                    exit;
                }
                
                // Formatear datos
                $peso = $_POST['peso'] ?? null;
                if ($peso !== null && $peso !== '') {
                    $peso = floatval($peso);
                } else {
                    $peso = null;
                }
                
                // Preparar consulta para insertar paciente
                $stmt = $pdo->prepare("
                    INSERT INTO pacientes 
                    (numero_box, nombre_completo, edad, peso, numero_historia, fecha_ingreso, estado) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'activo')
                ");
                
                $result = $stmt->execute([
                    $_POST['box'],
                    $_POST['nombre_completo'],
                    intval($_POST['edad']),
                    $peso,
                    $_POST['numero_historia']
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Paciente creado correctamente']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Error al crear el paciente']);
                }
                break;
                
            case 'actualizar_datos':
                // Validación de campos obligatorios
                if (
                    empty($_POST['nombre_completo']) ||
                    empty($_POST['edad']) ||
                    empty($_POST['numero_historia']) ||
                    empty($_POST['box'])
                ) {
                    echo json_encode(['success' => false, 'error' => 'Faltan campos obligatorios']);
                    exit;
                }
                
                // Formatear datos
                $peso = $_POST['peso'] ?? null;
                if ($peso !== null && $peso !== '') {
                    $peso = floatval($peso);
                } else {
                    $peso = null;
                }
                
                // Preparar consulta para actualizar paciente
                $stmt = $pdo->prepare("
                    UPDATE pacientes SET 
                        nombre_completo = ?, 
                        edad = ?, 
                        peso = ?, 
                        numero_historia = ?
                    WHERE numero_box = ? AND estado = 'activo'
                ");
                
                $result = $stmt->execute([
                    $_POST['nombre_completo'],
                    intval($_POST['edad']),
                    $peso,
                    $_POST['numero_historia'],
                    $_POST['box']
                ]);
                
                if ($result && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Datos actualizados correctamente']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el paciente']);
                }
                break;
                
            case 'alta_paciente':
                // ✅ FUNCIÓN PARA DAR DE ALTA
                $box = $_POST['box'] ?? null;
                if (!$box) {
                    echo json_encode(['success' => false, 'error' => 'Box no especificado']);
                    exit;
                }
                
                // Obtener información del paciente antes del alta
                $stmtInfo = $pdo->prepare("SELECT nombre_completo FROM pacientes WHERE numero_box = ? AND estado = 'activo'");
                $stmtInfo->execute([$box]);
                $pacienteInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
                
                if (!$pacienteInfo) {
                    echo json_encode(['success' => false, 'error' => 'No se encontró paciente activo en el box especificado']);
                    exit;
                }
                
                // Cambiar estado del paciente a 'alta'
                $stmt = $pdo->prepare("
                    UPDATE pacientes 
                    SET estado = 'alta', fecha_alta = NOW() 
                    WHERE numero_box = ? AND estado = 'activo'
                ");
                
                $result = $stmt->execute([$box]);
                
                if ($result && $stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Paciente dado de alta correctamente',
                        'paciente' => $pacienteInfo['nombre_completo']
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Error al procesar el alta médica']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción POST no válida']);
                break;
        }
    }
    
} catch (Exception $e) {
    error_log("Error en gestionar_pacientes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>