<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Verificación y Limpieza de Tablas</h1>";
echo "<hr>";

// Configuración de la base de datos
$servername = "localhost";
$username = "u724879249_data";
$password = "Farolill0.1";
$dbname = "u724879249_data";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📊 Tablas Encontradas:</h2>";
    
    // Mostrar todas las tablas relacionadas con pacientes
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $tablasPacientes = array_filter($tablas, function($tabla) {
        return strpos($tabla, 'pacientes') !== false;
    });
    
    foreach ($tablasPacientes as $tabla) {
        echo "<h3>🗂️ Tabla: <strong>$tabla</strong></h3>";
        
        // Mostrar estructura
        $stmt = $pdo->query("DESCRIBE $tabla");
        $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
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
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
        $result = $stmt->fetch();
        echo "<p><strong>Registros:</strong> " . $result['total'] . "</p>";
        
        // Mostrar algunos datos si existen
        if ($result['total'] > 0) {
            echo "<p><strong>Primeros 3 registros:</strong></p>";
            $stmt = $pdo->query("SELECT * FROM $tabla LIMIT 3");
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($registros)) {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
                echo "<tr>";
                foreach (array_keys($registros[0]) as $columna) {
                    echo "<th>$columna</th>";
                }
                echo "</tr>";
                
                foreach ($registros as $registro) {
                    echo "<tr>";
                    foreach ($registro as $valor) {
                        echo "<td>" . htmlspecialchars($valor) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        echo "<hr style='margin: 20px 0;'>";
    }
    
    echo "<h2>🎯 Recomendación:</h2>";
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #856404; margin: 20px 0;'>";
    echo "<p><strong>Basándome en la estructura que veo:</strong></p>";
    echo "<ul>";
    echo "<li>✅ <strong>Tabla 'pacientes'</strong> tiene la estructura correcta</li>";
    echo "<li>❓ <strong>Tabla 'pacientes_boxes'</strong> parece ser diferente</li>";
    echo "<li>🔧 <strong>Recomendación:</strong> Usar solo 'pacientes' y eliminar 'pacientes_boxes'</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>⚡ Acciones de Limpieza:</h2>";
    
    if (isset($_GET['accion'])) {
        switch($_GET['accion']) {
            case 'eliminar_pacientes_boxes':
                try {
                    $pdo->exec("DROP TABLE IF EXISTS pacientes_boxes");
                    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
                    echo "✅ Tabla 'pacientes_boxes' eliminada correctamente";
                    echo "</div>";
                } catch(PDOException $e) {
                    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
                    echo "❌ Error al eliminar: " . $e->getMessage();
                    echo "</div>";
                }
                break;
                
            case 'limpiar_pacientes':
                try {
                    $pdo->exec("DELETE FROM pacientes");
                    $pdo->exec("ALTER TABLE pacientes AUTO_INCREMENT = 1");
                    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
                    echo "✅ Tabla 'pacientes' limpiada correctamente";
                    echo "</div>";
                } catch(PDOException $e) {
                    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
                    echo "❌ Error al limpiar: " . $e->getMessage();
                    echo "</div>";
                }
                break;
                
            case 'verificar_estructura':
                // Verificar que la tabla pacientes tenga todos los campos necesarios
                $camposRequeridos = [
                    'id', 'numero_box', 'nombre_completo', 'edad', 'peso', 
                    'numero_historia', 'fecha_ingreso', 'fecha_alta', 'estado', 'fecha_creacion'
                ];
                
                $stmt = $pdo->query("DESCRIBE pacientes");
                $camposExistentes = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
                
                $camposFaltantes = array_diff($camposRequeridos, $camposExistentes);
                
                if (empty($camposFaltantes)) {
                    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
                    echo "✅ La tabla 'pacientes' tiene todos los campos requeridos";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
                    echo "❌ Faltan campos: " . implode(', ', $camposFaltantes);
                    echo "</div>";
                }
                break;
        }
        
        // Recargar la página para mostrar cambios
        echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
    }
    
    echo "<div style='margin: 20px 0;'>";
    echo "<h3>🔧 Acciones Disponibles:</h3>";
    echo "<p><a href='?accion=verificar_estructura' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 5px;'>🔍 Verificar Estructura</a></p>";
    echo "<p><a href='?accion=limpiar_pacientes' style='background: #ffc107; color: black; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 5px;' onclick='return confirm(\"¿Estás seguro de limpiar todos los pacientes?\")'>🧹 Limpiar Tabla Pacientes</a></p>";
    echo "<p><a href='?accion=eliminar_pacientes_boxes' style='background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 5px;' onclick='return confirm(\"¿Estás seguro de eliminar la tabla pacientes_boxes?\")'>🗑️ Eliminar Tabla pacientes_boxes</a></p>";
    echo "</div>";
    
    echo "<h3>📋 Siguiente Paso:</h3>";
    echo "<p>1. Verificar qué tabla es la correcta</p>";
    echo "<p>2. Eliminar la tabla innecesaria</p>";
    echo "<p>3. Limpiar la tabla que vamos a usar</p>";
    echo "<p>4. Probar crear un paciente</p>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Error de Conexión</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f8f9fa;
}
h1, h2, h3 { 
    color: #333; 
}
table { 
    border-collapse: collapse; 
    background: white;
    font-size: 12px;
}
th, td { 
    padding: 8px; 
    border: 1px solid #ddd; 
    text-align: left; 
}
th { 
    background: #e9ecef; 
    font-weight: bold;
}
a {
    display: inline-block;
    margin: 5px;
}
</style>