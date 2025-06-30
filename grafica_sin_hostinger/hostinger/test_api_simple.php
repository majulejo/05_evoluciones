<?php
require_once 'database.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test API UCI</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        h1 { color: #2c3e50; }
        h2 { color: #3498db; }
    </style>
</head>
<body>
    <h1>🧪 Test API UCI - Versión Simple</h1>
    
    <?php
    $tests = [];
    
    // Test 1: Base de datos
    echo "<div class='test'><h2>🔌 Test 1: Base de Datos</h2>";
    try {
        $db = new Database();
        if($db->testConnection()) {
            echo "<div class='success'>✅ Conexión exitosa</div>";
            $tests['db'] = true;
        } else {
            echo "<div class='error'>❌ Conexión falló</div>";
            $tests['db'] = false;
        }
    } catch(Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        $tests['db'] = false;
    }
    echo "</div>";
    
    // Test 2: Tablas
    echo "<div class='test'><h2>🗃️ Test 2: Tablas</h2>";
    $tablas = ['pacientes', 'constantes_vitales', 'oxigenacion_dolor', 'perdidas', 'balances_diarios'];
    $encontradas = 0;
    
    foreach($tablas as $tabla) {
        try {
            $db->executeQuery("SELECT 1 FROM $tabla LIMIT 1");
            echo "<div class='success'>✅ Tabla $tabla existe</div>";
            $encontradas++;
        } catch(Exception $e) {
            echo "<div class='error'>❌ Tabla $tabla falta</div>";
        }
    }
    $tests['tables'] = $encontradas === count($tablas);
    echo "</div>";
    
    // Test 3: APIs
    echo "<div class='test'><h2>🔗 Test 3: APIs</h2>";
    $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
    $apis = [
        'pacientes' => '/api/pacientes.php?resumen_camas=1',
        'sync' => '/api/sync.php?test_connection=1'
    ];
    
    $apisOK = 0;
    foreach($apis as $nombre => $endpoint) {
        $url = $baseUrl . $endpoint;
        $response = @file_get_contents($url);
        
        if($response && strpos($response, '"success":true') !== false) {
            echo "<div class='success'>✅ API $nombre funciona</div>";
            $apisOK++;
        } else {
            echo "<div class='error'>❌ API $nombre falló</div>";
            echo "<div class='error'>URL: $url</div>";
        }
    }
    $tests['apis'] = $apisOK === count($apis);
    echo "</div>";
    
    // Test 4: Archivos
    echo "<div class='test'><h2>📁 Test 4: Archivos</h2>";
    $archivos = ['database.php', 'api/pacientes.php', 'api/sync.php', 'api/constantes.php'];
    $archivosOK = 0;
    
    foreach($archivos as $archivo) {
        if(file_exists($archivo)) {
            echo "<div class='success'>✅ $archivo existe</div>";
            $archivosOK++;
        } else {
            echo "<div class='error'>❌ $archivo falta</div>";
        }
    }
    $tests['files'] = $archivosOK === count($archivos);
    echo "</div>";
    
    // Resumen
    $exitosos = array_sum($tests);
    $total = count($tests);
    
    echo "<div class='test'><h2>📊 Resumen Final</h2>";
    if($exitosos === $total) {
        echo "<div class='success'>";
        echo "<h3>🎉 ¡TODO PERFECTO! ($exitosos/$total tests exitosos)</h3>";
        echo "<p><strong>Tu sistema UCI está listo para usar.</strong></p>";
        echo "<p>Siguiente paso: Añade <code>&lt;script src=\"api-integration.js\"&gt;&lt;/script&gt;</code> en tus HTML</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>⚠️ Hay errores ($exitosos/$total tests exitosos)</h3>";
        echo "<p>Revisa los errores arriba y corrige la configuración.</p>";
        echo "</div>";
    }
    echo "</div>";
    ?>
    
    <div class="test">
        <h2>⚡ Acciones</h2>
        <button onclick="window.location.reload()" style="padding: 10px 20px; margin: 5px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">🔄 Ejecutar Tests Nuevamente</button>
        <button onclick="window.open('<?php echo $baseUrl; ?>/api/sync.php?test_connection=1', '_blank')" style="padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">🌐 Test Conectividad</button>
    </div>
    
    <div class="test">
        <h2>ℹ️ Info</h2>
        <p><strong>PHP:</strong> <?php echo phpversion(); ?></p>
        <p><strong>Base URL:</strong> <?php echo $baseUrl; ?></p>
        <p><strong>Fecha:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</body>
</html>