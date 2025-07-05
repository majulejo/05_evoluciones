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
            'unidades' => 0, 
            'tipo' => 'NADA', 
            'observacion' => 'Glucemia normal'
        ];
    } elseif ($glucemia >= 151 && $glucemia <= 225) {
        return [
            'unidades' => 6, 
            'tipo' => 's/c', 
            'observacion' => '6 U.I. s/c'
        ];
    } elseif ($glucemia >= 226 && $glucemia <= 250) {
        return [
            'unidades' => 10, 
            'tipo' => 's/c', 
            'observacion' => '10 U.I. s/c'
        ];
    } elseif ($glucemia >= 251 && $glucemia <= 300) {
        return [
            'unidades' => 15, 
            'tipo' => 's/c', 
            'observacion' => '15 U.I. s/c'
        ];
    } elseif ($glucemia >= 301 && $glucemia <= 350) {
        return [
            'unidades' => 20, 
            'tipo' => 's/c', 
            'observacion' => '20 U.I. s/c'
        ];
    } elseif ($glucemia >= 351 && $glucemia <= 400) {
        return [
            'unidades' => 20, 
            'tipo' => 's/c + I.V.', 
            'observacion' => '20 U.I. s/c + 5 U.I. I.V. - AVISAR AL FACULTATIVO'
        ];
    } elseif ($glucemia > 400) {
        return [
            'unidades' => 20, 
            'tipo' => 'perfusión', 
            'observacion' => 'Perfusión continua - AVISAR AL FACULTATIVO'
        ];
    }
    
    return [
        'unidades' => 0, 
        'tipo' => 's/c', 
        'observacion' => 'Revisar protocolo'
    ];
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $glucemia = floatval($input['glucemia'] ?? 0);
    
    if ($glucemia <= 0) {
        throw new Exception('Valor de glucemia requerido');
    }
    
    $recomendacion = calcularInsulinaRecomendada($glucemia);
    
    echo json_encode([
        'success' => true,
        'glucemia' => $glucemia,
        'insulina_recomendada' => $recomendacion,
        'message' => 'Cálculo de insulina realizado'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>