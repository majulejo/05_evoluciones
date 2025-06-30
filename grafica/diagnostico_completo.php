<?php
// Diagnóstico completo del sistema
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Diagnóstico Completo del Sistema</h1>";
echo "<hr>";

// 1. Test básico de conexión
echo "<h2>1. 📡 Test de Conexión Básica</h2>";
$servername = "localhost";
$username = "u724879249_data";
$password = "Farolill0.1";
$dbname = "u724879249_data";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión básica: <strong>EXITOSA</strong><br>";
} catch(PDOException $e) {
    echo "❌ Conexión básica: <strong>FALLÓ</strong> - " . $e->getMessage() . "<br>";
    exit;
}

// 2. Test de la API gestionar_pacientes.php
echo "<h2>2. 🔧 Test de gestionar_pacientes.php</h2>";

// Simular petición GET
echo "<h3>Test GET - obtener_paciente:</h3>";
$_GET['accion'] = 'obtener_paciente';
$_GET['box'] = '1';

ob_start();
include 'gestionar_pacientes.php';
$response_get = ob_get_contents();
ob_end_clean();

echo "Raw response: <pre>" . htmlspecialchars($response_get) . "</pre>";

$json_get = json_decode($response_get, true);
if ($json_get !== null) {
    echo "✅ JSON válido<br>";
    echo "Success: " . ($json_get['success'] ? 'true' : 'false') . "<br>";
    if (!$json_get['success']) {
        echo "Error: " . $json_get['error'] . "<br>";
    }
} else {
    echo "❌ JSON inválido<br>";
}

// Limpiar variables
unset($_GET['accion']);
unset($_GET['box']);

echo "<h3>Test POST - crear_paciente:</h3>";
$_POST['accion'] = 'crear_paciente';
$_POST['box'] = '999';
$_POST['nombre_completo'] = 'Paciente de Prueba';
$_POST['edad'] = '30';
$_POST['peso'] = '70';
$_POST['numero_historia'] = 'TEST-001';

ob_start();
include 'gestionar_pacientes.php';
$response_post = ob_get_contents();
ob_end_clean();

echo "Raw response: <pre>" . htmlspecialchars($response_post) . "</pre>";

$json_post = json_decode($response_post, true);
if ($json_post !== null) {
    echo "✅ JSON válido<br>";
    echo "Success: " . ($json_post['success'] ? 'true' : 'false') . "<br>";
    if (!$json_post['success']) {
        echo "Error: " . $json_post['error'] . "<br>";
    }
} else {
    echo "❌ JSON inválido<br>";
}

// Limpiar el paciente de prueba
if ($json_post && $json_post['success']) {
    try {
        $stmt = $pdo->prepare("DELETE FROM pacientes WHERE numero_box = '999'");
        $stmt->execute();
        echo "🧹 Paciente de prueba eliminado<br>";
    } catch(PDOException $e) {
        echo "⚠️ No se pudo eliminar paciente de prueba: " . $e->getMessage() . "<br>";
    }
}

// Limpiar variables POST
unset($_POST);

// 3. Test directo de la tabla
echo "<h2>3. 📋 Test Directo de la Tabla</h2>";

try {
    // Verificar estructura
    $stmt = $pdo->query("DESCRIBE pacientes");
    $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estructura de la tabla:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    foreach ($campos as $campo) {
        echo "<tr><td>{$campo['Field']}</td><td>{$campo['Type']}</td><td>{$campo['Null']}</td><td>{$campo['Key']}</td></tr>";
    }
    echo "</table>";
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pacientes");
    $result = $stmt->fetch();
    echo "<br>📊 Total registros: " . $result['total'] . "<br>";
    
    // Test de INSERT directo
    echo "<h3>Test INSERT directo:</h3>";
    $stmt = $pdo->prepare("INSERT INTO pacientes (numero_box, nombre_completo, edad, peso, numero_historia, fecha_ingreso, estado) VALUES (?, ?, ?, ?, ?, NOW(), 'activo')");
    $stmt->execute(['TEST', 'Prueba Directa', 25, 65, 'DIRECT-001']);
    echo "✅ INSERT directo exitoso<br>";
    
    // Test de SELECT directo
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE numero_box = 'TEST'");
    $stmt->execute();
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ SELECT directo exitoso: " . $paciente['nombre_completo'] . "<br>";
    
    // Limpiar
    $stmt = $pdo->prepare("DELETE FROM pacientes WHERE numero_box = 'TEST'");
    $stmt->execute();
    echo "🧹 Registro de prueba eliminado<br>";
    
} catch(PDOException $e) {
    echo "❌ Error en test directo: " . $e->getMessage() . "<br>";
}

// 4. Test de datos.html simulado
echo "<h2>4. 🌐 Test de Solicitud desde datos.html</h2>";

// Simular exactamente lo que hace datos.html
$url = 'gestionar_pacientes.php?accion=obtener_paciente&box=1&t=' . time();

echo "URL que usaría datos.html: $url<br>";

// Simular con cURL
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/gestionar_pacientes.php?accion=obtener_paciente&box=1&t=' . time());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $http_code<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
    
    $json = json_decode($response, true);
    if ($json !== null) {
        echo "✅ JSON válido desde cURL<br>";
    } else {
        echo "❌ JSON inválido desde cURL<br>";
    }
} else {
    echo "⚠️ cURL no disponible para test<br>";
}

// 5. Verificar errores de PHP
echo "<h2>5. ⚙️ Configuración PHP</h2>";
echo "Display Errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";
echo "Log Errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "<br>";

// 6. Test completo de crear paciente
echo "<h2>6. 🎯 Test Completo: Crear Paciente</h2>";

try {
    // Limpiar cualquier paciente en box 1
    $stmt = $pdo->prepare("DELETE FROM pacientes WHERE numero_box = '1'");
    $stmt->execute();
    echo "🧹 Box 1 limpiado<br>";
    
    // Crear paciente exactamente como lo haría datos.html
    $formData = [
        'accion' => 'crear_paciente',
        'box' => '1',
        'nombre_completo' => 'Juan Pérez Test',
        'edad' => '45',
        'peso' => '75',
        'numero_historia' => 'H-12345'
    ];
    
    // Simular POST
    $_POST = $formData;
    
    ob_start();
    include 'gestionar_pacientes.php';
    $create_response = ob_get_contents();
    ob_end_clean();
    
    echo "Response crear paciente: <pre>" . htmlspecialchars($create_response) . "</pre>";
    
    $create_json = json_decode($create_response, true);
    if ($create_json && $create_json['success']) {
        echo "✅ <strong>PACIENTE CREADO EXITOSAMENTE</strong><br>";
        
        // Ahora probar obtener
        unset($_POST);
        $_GET['accion'] = 'obtener_paciente';
        $_GET['box'] = '1';
        
        ob_start();
        include 'gestionar_pacientes.php';
        $get_response = ob_get_contents();
        ob_end_clean();
        
        echo "Response obtener paciente: <pre>" . htmlspecialchars($get_response) . "</pre>";
        
        $get_json = json_decode($get_response, true);
        if ($get_json && $get_json['success']) {
            echo "✅ <strong>PACIENTE OBTENIDO EXITOSAMENTE</strong><br>";
            echo "Nombre: " . $get_json['paciente']['nombre_completo'] . "<br>";
        } else {
            echo "❌ Error al obtener paciente<br>";
        }
    } else {
        echo "❌ Error al crear paciente<br>";
        if ($create_json) {
            echo "Error: " . $create_json['error'] . "<br>";
        }
    }
    
} catch(Exception $e) {
    echo "❌ Excepción en test completo: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>📋 Resumen:</h2>";
echo "<p>Si todos los tests anteriores son exitosos, el problema podría estar en:</p>";
echo "<ul>";
echo "<li>Cache del navegador</li>";
echo "<li>Problema de CORS</li>";
echo "<li>Error de JavaScript en datos.html</li>";
echo "<li>Problema de encoding/charset</li>";
echo "</ul>";

echo "<h3>🔧 Próximo paso:</h3>";
echo "<p><a href='datos.html?box=1&action=nuevo' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🧪 Probar datos.html</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h1, h2, h3 { color: #333; }
table { border-collapse: collapse; background: white; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #e9ecef; }
pre { background: #f8f8f8; padding: 10px; border-radius: 4px; font-size: 12px; }
ul { margin-left: 20px; }
</style>