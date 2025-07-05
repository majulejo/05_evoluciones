<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $pdo = obtenerConexionBD();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $box = $input['box'] ?? '';
    $hora = $input['hora'] ?? '';
    $modo = $input['modo'] ?? 'subcutanea'; // 'subcutanea', 'iv', 'mixta'
    $unidades_sc = floatval($input['unidades_sc'] ?? 0);
    $unidades_iv = floatval($input['unidades_iv'] ?? 0);
    $observacion = $input['observacion'] ?? '';
    
    if (empty($box) || empty($hora)) {
        throw new Exception('Box y hora son requeridos');
    }
    
    // Validar según el modo
    if ($modo === 'subcutanea' && $unidades_sc <= 0) {
        throw new Exception('Unidades subcutáneas requeridas para modo subcutáneo');
    }
    
    if ($modo === 'iv' && $unidades_iv <= 0) {
        throw new Exception('Unidades I.V. requeridas para modo intravenoso');
    }
    
    if ($modo === 'mixta' && ($unidades_sc <= 0 || $unidades_iv <= 0)) {
        throw new Exception('Ambos tipos de unidades requeridas para modo mixto');
    }
    
    // Formatear valores según el tipo
    if ($modo === 'subcutanea') {
        $unidades_sc = round($unidades_sc); // Sin decimales para s/c
        $unidades_iv = 0;
        $tipo_texto = $unidades_sc . ' U.I. s/c';
    } elseif ($modo === 'iv') {
        $unidades_sc = 0;
        $unidades_iv = round($unidades_iv, 1); // 1 decimal para I.V.
        $tipo_texto = $unidades_iv . ' U.I. I.V.';
    } else { // mixta
        $unidades_sc = round($unidades_sc); // Sin decimales para s/c
        $unidades_iv = round($unidades_iv, 1); // 1 decimal para I.V.
        $tipo_texto = $unidades_sc . ' U.I. s/c + ' . $unidades_iv . ' U.I. I.V.';
    }
    
    // Calcular fecha del turno
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');
    
    if ($hora_actual < '08:00:00') {
        $fecha_actual = date('Y-m-d', strtotime('-1 day'));
    }
    
    // Verificar si ya existe un registro
    $stmt = $pdo->prepare("
        SELECT id FROM datos_oxigenacion 
        WHERE numero_box = ? AND hora = ? AND fecha = ?
    ");
    $stmt->execute([$box, $hora, $fecha_actual]);
    $existe = $stmt->fetch();
    
    if ($existe) {
        // Actualizar registro existente
        $stmt = $pdo->prepare("
            UPDATE datos_oxigenacion 
            SET insulina = ?, insulina_iv = ?, modo_insulina = ?, tipo_insulina = ?, observacion_insulina = ?
            WHERE numero_box = ? AND hora = ? AND fecha = ?
        ");
        $stmt->execute([
            $unidades_sc,
            $unidades_iv,
            $modo,
            $tipo_texto,
            $observacion,
            $box, $hora, $fecha_actual
        ]);
    } else {
        // Crear nuevo registro
        $stmt = $pdo->prepare("
            INSERT INTO datos_oxigenacion 
            (numero_box, hora, fecha, insulina, insulina_iv, modo_insulina, tipo_insulina, observacion_insulina)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $box, $hora, $fecha_actual,
            $unidades_sc,
            $unidades_iv,
            $modo,
            $tipo_texto,
            $observacion
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'unidades_sc' => $unidades_sc,
            'unidades_iv' => $unidades_iv,
            'modo' => $modo,
            'tipo_texto' => $tipo_texto,
            'observacion' => $observacion
        ],
        'message' => 'Insulina guardada correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>