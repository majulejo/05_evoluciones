<?php
// Script de diagnóstico para verificar conexión y errores
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔍 Diagnóstico del Sistema</h2>";
echo "<hr>";

// 1. Verificar configuración de PHP
echo "<h3>1. Configuración PHP:</h3>";
echo "Versión PHP: " . phpversion() . "<br>";
echo "Extensión PDO: " . (extension_loaded('pdo') ? '✅ Instalada' : '❌ No instalada') . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Instalada' : '❌ No instalada') . "<br>";
echo "Display Errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>";
echo "<br>";

// 2. Probar conexión a la base de datos
echo "<h3>2. Prueba de Conexión:</h3>";
$servername = "localhost";
$username = "u724879249_data";
$password = "Farolill0.1";
$dbname = "u724879249_data";

try {
    echo "Intentando conectar a: $servername<br>";
    echo "Base de datos: $dbname<br>";
    echo "Usuario: $username<br>";
    
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ <strong>Conexión exitosa!</strong><br><br>";
    
    // 3. Verificar si existe la tabla pacientes
    echo "<h3>3. Verificación de Tablas:</h3>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tablas encontradas:<br>";
    foreach ($tablas as $tabla) {
        echo "• $tabla";
        if ($tabla === 'pacientes') {
            echo " ✅";
        }
        echo "<br>";
    }
    
    if (in_array('pacientes', $tablas)) {
        echo "<br><h3>4. Estructura de tabla 'pacientes':</h3>";
        $stmt = $pdo->query("DESCRIBE pacientes");
        $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($campos as $campo) {
            echo "<tr>";
            echo "<td>" . $campo['Field'] . "</td>";
            echo "<td>" . $campo['Type'] . "</td>";
            echo "<td>" . $campo['Null'] . "</td>";
            echo "<td>" . $campo['Key'] . "</td>";
            echo "<td>" . $campo['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 5. Verificar datos existentes
        echo "<h3>5. Datos Existentes:</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pacientes");
        $result = $stmt->fetch();
        echo "Total pacientes: " . $result['total'] . "<br>";
        
        if ($result['total'] > 0) {
            echo "<br>Pacientes existentes:<br>";
            $stmt = $pdo->query("SELECT numero_box, nombre_completo, estado, fecha_ingreso FROM pacientes ORDER BY fecha_ingreso DESC LIMIT 5");
            $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Box</th><th>Nombre</th><th>Estado</th><th>Fecha Ingreso</th></tr>";
            foreach ($pacientes as $p) {
                echo "<tr>";
                echo "<td>" . $p['numero_box'] . "</td>";
                echo "<td>" . $p['nombre_completo'] . "</td>";
                echo "<td>" . $p['estado'] . "</td>";
                echo "<td>" . $p['fecha_ingreso'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<br>❌ <strong>Tabla 'pacientes' no encontrada</strong><br>";
        echo "Ejecutar este SQL para crearla:<br>";
        echo "<textarea style='width: 100%; height: 200px;'>";
        echo "CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_box VARCHAR(10) NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    edad INT,
    peso DECIMAL(5,2),
    numero_historia VARCHAR(100),
    fecha_ingreso DATETIME NOT NULL,
    fecha_alta DATETIME NULL,
    estado ENUM('activo', 'alta') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);";
        echo "</textarea>";
    }
    
} catch(PDOException $e) {
    echo "❌ <strong>Error de conexión:</strong><br>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Código: " . $e->getCode() . "<br>";
    
    if ($e->getCode() == 1045) {
        echo "<br>🔍 <strong>Sugerencias:</strong><br>";
        echo "• Verificar usuario y contraseña<br>";
        echo "• Verificar que el usuario tenga permisos<br>";
    } elseif ($e->getCode() == 1049) {
        echo "<br>🔍 <strong>Sugerencias:</strong><br>";
        echo "• La base de datos no existe<br>";
        echo "• Verificar el nombre de la base de datos<br>";
    } elseif ($e->getCode() == 2002) {
        echo "<br>🔍 <strong>Sugerencias:</strong><br>";
        echo "• El servidor MySQL no está ejecutándose<br>";
        echo "• Verificar la dirección del servidor<br>";
    }
}

// 6. Probar gestionar_pacientes.php
echo "<br><h3>6. Prueba de gestionar_pacientes.php:</h3>";

if (file_exists('gestionar_pacientes.php')) {
    echo "✅ Archivo gestionar_pacientes.php existe<br>";
    
    // Capturar cualquier error de syntax
    $check = file_get_contents('gestionar_pacientes.php');
    if (strpos($check, '<?php') !== false) {
        echo "✅ Archivo tiene sintaxis PHP correcta<br>";
    } else {
        echo "❌ Archivo no tiene sintaxis PHP válida<br>";
    }
    
    // Probar una petición simple
    echo "<br>Probando petición GET...<br>";
    
    // Simular petición GET
    $_GET['accion'] = 'obtener_pacientes_activos';
    
    ob_start();
    try {
        include 'gestionar_pacientes.php';
        $output = ob_get_contents();
        ob_end_clean();
        
        $json = json_decode($output, true);
        if ($json !== null) {
            echo "✅ Respuesta JSON válida<br>";
            echo "Contenido: " . htmlspecialchars($output) . "<br>";
        } else {
            echo "❌ Respuesta no es JSON válido<br>";
            echo "Output raw: <pre>" . htmlspecialchars($output) . "</pre>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Error al ejecutar: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ Archivo gestionar_pacientes.php no existe<br>";
}

echo "<hr>";
echo "<h3>📋 Resumen:</h3>";
echo "Ejecutar este script para diagnosticar problemas de conexión y configuración.<br>";
echo "Si hay errores, seguir las sugerencias mostradas arriba.<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h2, h3 { color: #333; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
pre { background: #f8f8f8; padding: 10px; border-radius: 4px; }
textarea { font-family: monospace; font-size: 12px; }
</style>