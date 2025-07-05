<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Función para calcular insulina recomendada basada en glucemia
function calcularInsulinaRecomendada($glucemia) {
    // Protocolo según la imagen proporcionada
    if ($glucemia < 150) {
        return [
            'unidades_sc' => 0,
            'unidades_iv' => 0,
            'modo' => 'subcutanea',
            'tipo' => 'NADA', 
            'observacion' => 'Glucemia normal'
        ];
    } elseif ($glucemia >= 151 && $glucemia <= 225) {
        return [
            'unidades_sc' => 6,
            'unidades_iv' => 0,
            'modo' => 'subcutanea',
            'tipo' => 's/c', 
            'observacion' => '6 U.I. s/c'
        ];
    } elseif ($glucemia >= 226 && $glucemia <= 250) {
        return [
            'unidades_sc' => 10,
            'unidades_iv' => 0,
            'modo' => 'subcutanea',
            'tipo' => 's/c', 
            'observacion' => '10 U.I. s/c'
        ];
    } elseif ($glucemia >= 251 && $glucemia <= 300) {
        return [
            'unidades_sc' => 15,
            'unidades_iv' => 0,
            'modo' => 'subcutanea',
            'tipo' => 's/c', 
            'observacion' => '15 U.I. s/c'
        ];
    } elseif ($glucemia >= 301 && $glucemia <= 350) {
        return [
            'unidades_sc' => 20,
            'unidades_iv' => 0,
            'modo' => 'subcutanea',
            'tipo' => 's/c', 
            'observacion' => '20 U.I. s/c'
        ];
    } elseif ($glucemia >= 351 && $glucemia <= 400) {
        return [
            'unidades_sc' => 20,
            'unidades_iv' => 5.0,
            'modo' => 'mixta',
            'tipo' => 's/c + I.V.', 
            'observacion' => '20 U.I. s/c + 5.0 U.I. I.V. - AVISAR AL FACULTATIVO'
        ];
    } elseif ($glucemia > 400) {
        return [
            'unidades_sc' => 0,
            'unidades_iv' => 2.4, // Ejemplo de perfusión inicial
            'modo' => 'iv',
            'tipo' => 'perfusión I.V.', 
            'observacion' => 'Perfusión continua I.V. - AVISAR AL FACULTATIVO'
        ];
    }
    
    return [
        'unidades_sc' => 0,
        'unidades_iv' => 0,
        'modo' => 'subcutanea',
        'tipo' => 's/c', 
        'observacion' => 'Revisar protocolo'
    ];
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $glucemia = floatval($input['glucemia'] ?? 0);
    $box = $input['box'] ?? '';
    $hora = $input['hora'] ?? '';
    
    if ($glucemia <= 0) {
        throw new Exception('Valor de glucemia requerido');
    }
    
    $recomendacion = calcularInsulinaRecomendada($glucemia);
    
    // Si se proporcionan box y hora, guardar automáticamente la recomendación
    if (!empty($box) && !empty($hora)) {
        $pdo = obtenerConexionBD();
        
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
            // Actualizar registro existente con datos de insulina
            $stmt = $pdo->prepare("
                UPDATE datos_oxigenacion 
                SET insulina = ?, insulina_iv = ?, modo_insulina = ?, tipo_insulina = ?, observacion_insulina = ?
                WHERE numero_box = ? AND hora = ? AND fecha = ?
            ");
            $stmt->execute([
                $recomendacion['unidades_sc'],
                $recomendacion['unidades_iv'],
                $recomendacion['modo'],
                $recomendacion['tipo'],
                $recomendacion['observacion'],
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
                $recomendacion['unidades_sc'],
                $recomendacion['unidades_iv'],
                $recomendacion['modo'],
                $recomendacion['tipo'],
                $recomendacion['observacion']
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'glucemia' => $glucemia,
        'insulina_recomendada' => $recomendacion,
        'guardado_automatico' => !empty($box) && !empty($hora),
        'message' => 'Cálculo de insulina realizado'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>