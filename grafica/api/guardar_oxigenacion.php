<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Configuración de base de datos
$host = 'localhost';
$dbname = 'u724879249_data';
$username = 'u724879249_data'; // Ajustar según tu configuración
$password = 'Farolill0.1'; // Ajustar según tu configuración

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $box = $input['box'] ?? null;
    $hora = $input['hora'] ?? null;
    $campo = $input['campo'] ?? null;
    $valor = $input['valor'] ?? null;
    
    if (!$box || !$hora || !$campo) {
        echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']);
        exit;
    }
    
    try {
        // Mapear campos según tu estructura
        $campoDb = '';
        $valorDb = $valor;
        
        switch($campo) {
            case 'pNeumo':
                $campoDb = 'p_neumo';
                $valorDb = $valor !== null ? intval($valor) : null;
                break;
            case 'oxigenacion':
                $campoDb = 'oxigenacion';
                break;
            case 'saturacion':
                $campoDb = 'sat_o2';
                $valorDb = $valor !== null ? intval($valor) : null;
                break;
            case 'evaRass':
                // Para EVA/RASS, separar los valores
                $valorDb = $valor; // Guardar el valor completo como string
                $campoDb = 'eva_escid';
                break;
            case 'glucemia':
                $campoDb = 'glucemia';
                $valorDb = $valor !== null ? intval($valor) : null;
                break;
            case 'insulina':
                $campoDb = 'insulina';
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Campo no reconocido: ' . $campo]);
                exit;
        }
        
        // Verificar si ya existe registro
        $checkSql = "SELECT id FROM oxigenacion_datos WHERE numero_box = ? AND hora = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$box, $hora]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Actualizar registro existente
            $updateSql = "UPDATE oxigenacion_datos SET $campoDb = ?, updated_at = NOW() WHERE numero_box = ? AND hora = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$valorDb, $box, $hora]);
        } else {
            // Crear nuevo registro
            $insertSql = "INSERT INTO oxigenacion_datos (numero_box, hora, $campoDb, created_at) VALUES (?, ?, ?, NOW())";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([$box, $hora, $valorDb]);
        }
        
        // ✅ SINCRONIZACIÓN BIDIRECCIONAL
        // Si se actualiza saturación o glucemia en oxigenación, sincronizar con constantes vitales
        if ($campo === 'saturacion' || $campo === 'glucemia') {
            $campoConstantes = ($campo === 'saturacion') ? 'sat_o2' : 'glucemia';
            
            // Verificar si existe registro en constantes vitales
            $checkConstSql = "SELECT id FROM constantes_vitales WHERE numero_box = ? AND hora = ?";
            $checkConstStmt = $pdo->prepare($checkConstSql);
            $checkConstStmt->execute([$box, $hora]);
            $existingConst = $checkConstStmt->fetch();
            
            if ($existingConst) {
                // Actualizar registro existente en constantes
                $updateConstSql = "UPDATE constantes_vitales SET $campoConstantes = ? WHERE numero_box = ? AND hora = ?";
                $updateConstStmt = $pdo->prepare($updateConstSql);
                $updateConstStmt->execute([$valorDb, $box, $hora]);
            } else {
                // Crear nuevo registro en constantes vitales (solo con el campo modificado)
                $insertConstSql = "INSERT INTO constantes_vitales (numero_box, hora, $campoConstantes, fecha_registro) VALUES (?, ?, ?, NOW())";
                $insertConstStmt = $pdo->prepare($insertConstSql);
                $insertConstStmt->execute([$box, $hora, $valorDb]);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Dato de oxigenación guardado correctamente',
            'data' => [
                'box' => $box,
                'hora' => $hora,
                'campo' => $campo,
                'valor' => $valor
            ]
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>