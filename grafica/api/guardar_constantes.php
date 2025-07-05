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
    $fr = $input['fr'] ?? null;
    $temperatura = $input['temperatura'] ?? null;
    $fc = $input['fc'] ?? null;
    $ta_sistolica = $input['ta_sistolica'] ?? null;
    $ta_diastolica = $input['ta_diastolica'] ?? null;
    $saturacion = $input['saturacion'] ?? null;
    $glucemia = $input['glucemia'] ?? null;
    
    if (!$box || !$hora) {
        echo json_encode(['success' => false, 'message' => 'Box y hora son requeridos']);
        exit;
    }
    
    try {
        // Verificar si ya existe registro
        $checkSql = "SELECT id FROM constantes_vitales WHERE numero_box = ? AND hora = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$box, $hora]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Actualizar registro existente
            $updateSql = "UPDATE constantes_vitales SET 
                         fr = ?, temperatura = ?, fc = ?, ta_sistolica = ?, ta_diastolica = ?, sat_o2 = ?, glucemia = ?
                         WHERE numero_box = ? AND hora = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$fr, $temperatura, $fc, $ta_sistolica, $ta_diastolica, $saturacion, $glucemia, $box, $hora]);
        } else {
            // Crear nuevo registro
            $insertSql = "INSERT INTO constantes_vitales 
                         (numero_box, hora, fr, temperatura, fc, ta_sistolica, ta_diastolica, sat_o2, glucemia, fecha_registro) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([$box, $hora, $fr, $temperatura, $fc, $ta_sistolica, $ta_diastolica, $saturacion, $glucemia]);
        }
        
        // ✅ SINCRONIZAR AUTOMÁTICAMENTE CON TABLA DE OXIGENACIÓN
        // Actualizar saturación en oxigenación_datos si existe el registro
        if ($saturacion !== null) {
            $syncSatSql = "UPDATE oxigenacion_datos SET sat_o2 = ? WHERE numero_box = ? AND hora = ?";
            $syncSatStmt = $pdo->prepare($syncSatSql);
            $syncSatStmt->execute([$saturacion, $box, $hora]);
        }
        
        // Actualizar glucemia en oxigenación_datos si existe el registro
        if ($glucemia !== null) {
            $syncGlucSql = "UPDATE oxigenacion_datos SET glucemia = ? WHERE numero_box = ? AND hora = ?";
            $syncGlucStmt = $pdo->prepare($syncGlucSql);
            $syncGlucStmt->execute([$glucemia, $box, $hora]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Constantes vitales guardadas correctamente',
            'data' => [
                'box' => $box,
                'hora' => $hora,
                'fr' => $fr,
                'temperatura' => $temperatura,
                'fc' => $fc,
                'ta_sistolica' => $ta_sistolica,
                'ta_diastolica' => $ta_diastolica,
                'saturacion' => $saturacion,
                'glucemia' => $glucemia
            ]
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>