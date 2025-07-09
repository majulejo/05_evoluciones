<?php
// ===== VALIDADOR DE TODAS LAS APIs =====
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 VALIDADOR DE APIs</h1>";
echo "<pre>";

$baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

// APIs a probar
$apis = [
    'obtener_paciente' => ['url' => 'api/obtener_paciente.php?box=1', 'method' => 'GET'],
    'obtener_constantes' => ['url' => 'api/obtener_constantes.php?box=1&hoja=1', 'method' => 'GET'],
    'obtener_oxigenacion' => ['url' => 'api/obtener_oxigenacion.php?box=1&hoja=1', 'method' => 'GET'],
];

echo "🌐 Base URL: $baseUrl\n\n";

foreach ($apis as $name => $config) {
    echo "=== PROBANDO: $name ===\n";
    
    $fullUrl = $baseUrl . '/' . $config['url'];
    echo "🔗 URL: $fullUrl\n";
    
    try {
        $context = stream_context_create([
            'http' => [
                'method' => $config['method'],
                'timeout' => 10,
                'header' => [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ]
            ]
        ]);
        
        $response = @file_get_contents($fullUrl, false, $context);
        
        if ($response === false) {
            echo "❌ Error: No se pudo conectar\n";
            echo "📄 Headers: " . print_r($http_response_header ?? [], true) . "\n";
        } else {
            echo "✅ Respuesta recibida (" . strlen($response) . " bytes)\n";
            
            $data = json_decode($response, true);
            if ($data) {
                echo "📊 JSON válido: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
                echo "📋 Mensaje: " . ($data['message'] ?? 'N/A') . "\n";
                if (isset($data['count'])) {
                    echo "📈 Registros: " . $data['count'] . "\n";
                }
                if (isset($data['data']) && is_array($data['data'])) {
                    echo "📊 Datos: " . count($data['data']) . " elementos\n";
                }
            } else {
                echo "❌ JSON inválido\n";
                echo "📄 Respuesta: " . substr($response, 0, 200) . "...\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Excepción: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== PROBANDO GUARDADO ===\n";

// Probar guardar oxigenación
$saveUrl = $baseUrl . '/api/guardar_oxigenacion.php';
echo "🔗 URL guardado: $saveUrl\n";

$testData = json_encode([
    'box' => 1,
    'hora' => '14:00',
    'campo' => 'oxigenacion',
    'valor' => 'O2',
    'hoja' => 1
]);

try {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            'content' => $testData,
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($saveUrl, false, $context);
    
    if ($response === false) {
        echo "❌ Error guardando\n";
    } else {
        echo "✅ Guardado exitoso\n";
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "📊 Confirmación: " . $data['message'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error en guardado: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN ===\n";
echo "🎯 Si todas las APIs muestran ✅ SUCCESS, la aplicación debería funcionar\n";
echo "🔗 Prueba ahora: {$baseUrl}/grafica.html?box=1\n";

echo "</pre>";

// JavaScript para auto-redirección
echo "
<hr>
<div id='auto-test'></div>
<script>
document.getElementById('auto-test').innerHTML = '<p>🔄 Probando aplicación automáticamente en 3 segundos...</p>';

setTimeout(() => {
    window.location.href = 'grafica.html?box=1';
}, 3000);
</script>
";
?>