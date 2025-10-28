<?php
// repair_database.php - Script para reparar la estructura de la base de datos
// EJECUTAR SOLO UNA VEZ para corregir el problema

session_start();

// Verificar que sea un administrador o usuario autorizado
if (!isset($_SESSION['user_id'])) {
    die("No autorizado");
}

try {
    // Configuración de la base de datos
    $host = 'localhost';
    $dbname = 'u724879249_evolucion_uci';
    $username = 'u724879249_jamarquez06';
    $password = 'Farolill01.';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Reparando Base de Datos...</h2>";
    
    // 1. Verificar si la tabla existe
    $tables = $pdo->query("SHOW TABLES LIKE 'informes_guardados'")->fetchAll();
    
    if (empty($tables)) {
        echo "<p>❌ Tabla 'informes_guardados' no existe. Creándola...</p>";
        
        $createTable = "
        CREATE TABLE informes_guardados (
            id VARCHAR(36) PRIMARY KEY,
            user_id INT NOT NULL,
            box INT NOT NULL,
            datos JSON NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_box (user_id, box),
            INDEX idx_fecha (fecha_creacion)
        )";
        
        $pdo->exec($createTable);
        echo "<p>✅ Tabla 'informes_guardados' creada correctamente.</p>";
    } else {
        echo "<p>✅ Tabla 'informes_guardados' existe.</p>";
        
        // 2. Verificar columnas
        $columns = $pdo->query("SHOW COLUMNS FROM informes_guardados")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        echo "<p>Columnas actuales: " . implode(', ', $columnNames) . "</p>";
        
        // 3. Agregar columnas faltantes
        if (!in_array('fecha_creacion', $columnNames)) {
            echo "<p>⚠️ Falta columna 'fecha_creacion'. Agregándola...</p>";
            $pdo->exec("ALTER TABLE informes_guardados ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            echo "<p>✅ Columna 'fecha_creacion' agregada.</p>";
        }
        
        if (!in_array('fecha_modificacion', $columnNames)) {
            echo "<p>⚠️ Falta columna 'fecha_modificacion'. Agregándola...</p>";
            $pdo->exec("ALTER TABLE informes_guardados ADD COLUMN fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "<p>✅ Columna 'fecha_modificacion' agregada.</p>";
        }
    }
    
    // 4. Verificar estructura final
    echo "<h3>Estructura final de la tabla:</h3>";
    $finalColumns = $pdo->query("DESCRIBE informes_guardados")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>";
    foreach ($finalColumns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Probar inserción
    echo "<h3>Probando inserción de datos...</h3>";
    
    $testData = [
        'id' => 'test-' . uniqid(),
        'user_id' => $_SESSION['user_id'],
        'box' => 99,
        'datos' => json_encode(['test' => 'data'])
    ];
    
    $testStmt = $pdo->prepare("
        INSERT INTO informes_guardados (id, user_id, box, datos, fecha_creacion) 
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    if ($testStmt->execute([$testData['id'], $testData['user_id'], $testData['box'], $testData['datos']])) {
        echo "<p>✅ Inserción de prueba exitosa.</p>";
        
        // Eliminar registro de prueba
        $deleteStmt = $pdo->prepare("DELETE FROM informes_guardados WHERE id = ?");
        $deleteStmt->execute([$testData['id']]);
        echo "<p>✅ Registro de prueba eliminado.</p>";
    } else {
        echo "<p>❌ Error en inserción de prueba.</p>";
    }
    
    echo "<h2>✅ Reparación completada!</h2>";
    echo "<p>Ahora puedes volver a usar la aplicación normalmente.</p>";
    echo "<p><a href='app.php'>Volver a la aplicación</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de base de datos:</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Código: " . $e->getCode() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error general:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: #f5f5f5;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
}

th, td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background-color: #4CAF50;
    color: white;
}

p {
    margin: 10px 0;
    padding: 5px;
}

h2, h3 {
    color: #333;
}
</style>