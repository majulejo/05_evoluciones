<?php
// Archivo para probar únicamente las credenciales de la base de datos
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔐 Prueba de Credenciales MySQL</h1>";
echo "<hr>";

// Configuración de la base de datos
$servername = "localhost";
$username = "u724879249_data";
$password = "Farolill0.1";
$dbname = "u724879249_data";

echo "<h2>📋 Credenciales a probar:</h2>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Parámetro</th><th>Valor</th></tr>";
echo "<tr><td>Servidor</td><td>$servername</td></tr>";
echo "<tr><td>Usuario</td><td>$username</td></tr>";
echo "<tr><td>Contraseña</td><td>" . str_repeat('*', strlen($password)) . "</td></tr>";
echo "<tr><td>Base de datos</td><td>$dbname</td></tr>";
echo "</table>";

echo "<h2>🔄 Resultado de la conexión:</h2>";

try {
    // Intentar conexión
    echo "⏳ Intentando conectar...<br><br>";
    
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: #155724; margin: 0;'>✅ CONEXIÓN EXITOSA</h3>";
    echo "<p style='color: #155724; margin: 5px 0 0 0;'>Las credenciales son correctas y la conexión funciona perfectamente.</p>";
    echo "</div>";
    
    // Probar una consulta simple
    echo "<h3>🧪 Probando consultas:</h3>";
    
    // 1. Verificar si existe la tabla pacientes
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'pacientes'");
        $tabla_existe = $stmt->rowCount() > 0;
        
        if ($tabla_existe) {
            echo "✅ Tabla 'pacientes' existe<br>";
            
            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM pacientes");
            $result = $stmt->fetch();
            echo "📊 Total de registros en pacientes: " . $result['total'] . "<br>";
            
            // Probar inserción de prueba
            echo "<br><h4>🧪 Probando operaciones CRUD:</h4>";
            
            // INSERT de prueba
            $stmt = $pdo->prepare("INSERT INTO pacientes (numero_box, nombre_completo, edad, peso, numero_historia, fecha_ingreso, estado) VALUES (?, ?, ?, ?, ?, NOW(), 'activo')");
            $stmt->execute(['999', 'Prueba Sistema', 30, 70, 'TEST-001']);
            echo "✅ INSERT: Paciente de prueba creado<br>";
            
            // SELECT de prueba
            $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE numero_box = '999'");
            $stmt->execute();
            $paciente_prueba = $stmt->fetch();
            echo "✅ SELECT: Paciente recuperado - " . $paciente_prueba['nombre_completo'] . "<br>";
            
            // UPDATE de prueba
            $stmt = $pdo->prepare("UPDATE pacientes SET edad = 31 WHERE numero_box = '999'");
            $stmt->execute();
            echo "✅ UPDATE: Edad actualizada<br>";
            
            // DELETE de prueba
            $stmt = $pdo->prepare("DELETE FROM pacientes WHERE numero_box = '999'");
            $stmt->execute();
            echo "✅ DELETE: Paciente de prueba eliminado<br>";
            
        } else {
            echo "❌ Tabla 'pacientes' NO existe<br>";
            echo "<br><strong>Solución:</strong> Crear la tabla con este SQL:<br>";
            echo "<textarea style='width: 100%; height: 150px; font-family: monospace; font-size: 12px;'>";
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
        echo "❌ Error al verificar tabla: " . $e->getMessage() . "<br>";
    }
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>❌ ERROR DE CONEXIÓN</h3>";
    echo "<p style='color: #721c24; margin: 5px 0 0 0;'><strong>Código:</strong> " . $e->getCode() . "</p>";
    echo "<p style='color: #721c24; margin: 5px 0 0 0;'><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
    
    // Sugerencias basadas en el código de error
    echo "<h3>🔍 Diagnóstico y soluciones:</h3>";
    
    switch($e->getCode()) {
        case 1045:
            echo "<div style='background: #fff3cd; padding: 10px; border-left: 4px solid #856404;'>";
            echo "<strong>Error 1045 - Acceso Denegado:</strong><br>";
            echo "• El usuario o contraseña son incorrectos<br>";
            echo "• El usuario no tiene permisos en esta base de datos<br>";
            echo "• Verificar credenciales en el panel de hosting<br>";
            echo "• Asegurar que el usuario esté asignado a la base de datos<br>";
            echo "</div>";
            break;
            
        case 1049:
            echo "<div style='background: #fff3cd; padding: 10px; border-left: 4px solid #856404;'>";
            echo "<strong>Error 1049 - Base de datos no encontrada:</strong><br>";
            echo "• La base de datos '$dbname' no existe<br>";
            echo "• Verificar el nombre exacto en el panel de hosting<br>";
            echo "• Crear la base de datos si no existe<br>";
            echo "</div>";
            break;
            
        case 2002:
            echo "<div style='background: #fff3cd; padding: 10px; border-left: 4px solid #856404;'>";
            echo "<strong>Error 2002 - No se puede conectar al servidor:</strong><br>";
            echo "• El servidor MySQL no está ejecutándose<br>";
            echo "• El host '$servername' es incorrecto<br>";
            echo "• Problemas de red o firewall<br>";
            echo "</div>";
            break;
            
        default:
            echo "<div style='background: #fff3cd; padding: 10px; border-left: 4px solid #856404;'>";
            echo "<strong>Error desconocido:</strong><br>";
            echo "• Contactar al proveedor de hosting<br>";
            echo "• Verificar logs del servidor<br>";
            echo "• Revisar la documentación del hosting<br>";
            echo "</div>";
    }
}

echo "<hr>";
echo "<h3>📞 Siguiente paso:</h3>";
echo "<p>Si la conexión es exitosa, el sistema debería funcionar correctamente.</p>";
echo "<p>Si hay errores, seguir las sugerencias mostradas arriba.</p>";
echo "<p><a href='datos.html?box=1&action=nuevo' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🧪 Probar crear paciente</a></p>";

?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 40px; 
    background: #f8f9fa;
}
h1, h2, h3 { 
    color: #333; 
}
table { 
    border-collapse: collapse; 
    background: white;
}
th, td { 
    padding: 10px; 
    border: 1px solid #ddd; 
    text-align: left; 
}
th { 
    background: #e9ecef; 
    font-weight: bold;
}
textarea { 
    border: 1px solid #ddd; 
    border-radius: 4px; 
    padding: 10px;
}
</style>