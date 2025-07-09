<?php
// ===== SCRIPT PARA ARREGLAR TABLA PACIENTES =====
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 ARREGLANDO TABLA PACIENTES</h1>";
echo "<pre>";

try {
    require_once 'config.php';
    $pdo = obtenerConexionBD();
    
    echo "=== 1. VERIFICANDO ESTRUCTURA ACTUAL ===\n";
    
    // Ver estructura actual
    $stmt = $pdo->query("DESCRIBE pacientes");
    $columns = $stmt->fetchAll();
    
    echo "📋 Columnas actuales:\n";
    $hasBoxColumn = false;
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
        if ($col['Field'] === 'box') {
            $hasBoxColumn = true;
        }
    }
    
    echo "\n=== 2. VERIFICANDO DATOS EXISTENTES ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pacientes");
    $count = $stmt->fetch();
    echo "📊 Total de registros: {$count['total']}\n";
    
    if (!$hasBoxColumn) {
        echo "\n=== 3. AÑADIENDO COLUMNA 'box' ===\n";
        
        // Añadir columna box
        $pdo->exec("ALTER TABLE pacientes ADD COLUMN box INT NOT NULL DEFAULT 1 AFTER id");
        echo "✅ Columna 'box' añadida\n";
        
        // Crear índice
        $pdo->exec("ALTER TABLE pacientes ADD INDEX idx_box (box)");
        echo "✅ Índice en 'box' creado\n";
        
        // Actualizar registros existentes con boxes únicos
        $stmt = $pdo->query("SELECT id FROM pacientes ORDER BY id");
        $registros = $stmt->fetchAll();
        
        foreach ($registros as $index => $registro) {
            $boxNum = ($index % 10) + 1; // Distribuir en boxes 1-10
            $updateStmt = $pdo->prepare("UPDATE pacientes SET box = ? WHERE id = ?");
            $updateStmt->execute([$boxNum, $registro['id']]);
        }
        
        echo "✅ Registros actualizados con números de box\n";
        
    } else {
        echo "\n=== 3. COLUMNA 'box' YA EXISTE ===\n";
        echo "✅ No se necesitan cambios\n";
    }
    
    echo "\n=== 4. VERIFICANDO ESTRUCTURA FINAL ===\n";
    $stmt = $pdo->query("DESCRIBE pacientes");
    $columns = $stmt->fetchAll();
    
    echo "📋 Estructura final:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n=== 5. ASEGURAR DATOS DEMO ===\n";
    
    // Verificar si hay pacientes en boxes 1, 2, 3
    for ($box = 1; $box <= 3; $box++) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pacientes WHERE box = ?");
        $stmt->execute([$box]);
        $count = $stmt->fetch();
        
        if ($count['count'] == 0) {
            // No hay paciente en este box, crear uno
            $nombres = [
                1 => 'Plinicio Ruiz Ruiz (DEMO)',
                2 => 'María García López (DEMO)', 
                3 => 'Carlos Fernández Ruiz (DEMO)'
            ];
            
            $edades = [1 => 86, 2 => 72, 3 => 58];
            $pesos = [1 => 100.00, 2 => 65.50, 3 => 80.25];
            
            $insertSQL = "
                INSERT INTO pacientes (box, nombre_completo, edad, peso, numero_historia, fecha_ingreso, estado) 
                VALUES (?, ?, ?, ?, ?, '2025-01-07 17:09:44', 'activo')
            ";
            
            $stmt = $pdo->prepare($insertSQL);
            $stmt->execute([
                $box,
                $nombres[$box],
                $edades[$box],
                $pesos[$box],
                '1000' . $box
            ]);
            
            echo "✅ Paciente demo creado para box $box\n";
        } else {
            echo "✅ Box $box ya tiene paciente\n";
        }
    }
    
    echo "\n=== 6. VERIFICACIÓN FINAL ===\n";
    
    // Probar consultas
    for ($box = 1; $box <= 3; $box++) {
        $stmt = $pdo->prepare("SELECT nombre_completo, estado FROM pacientes WHERE box = ? AND estado = 'activo'");
        $stmt->execute([$box]);
        $paciente = $stmt->fetch();
        
        if ($paciente) {
            echo "✅ Box $box: {$paciente['nombre_completo']} ({$paciente['estado']})\n";
        } else {
            echo "❌ Box $box: Sin paciente activo\n";
        }
    }
    
    echo "\n🎉 ¡TABLA PACIENTES ARREGLADA!\n";
    echo "🔗 Ahora puedes probar: obtener_paciente.php?box=1\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📄 Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n" . date('Y-m-d H:i:s') . " - Reparación completada\n";
echo "</pre>";

// Auto-redirect para probar API
echo "<hr>";
echo "<h2>🧪 PROBANDO API AUTOMÁTICAMENTE</h2>";
echo "<div id='test-results'></div>";

echo "<script>
async function testAPI() {
    const resultsDiv = document.getElementById('test-results');
    
    try {
        resultsDiv.innerHTML = '<p>🔄 Probando API de pacientes...</p>';
        
        const response = await fetch('api/obtener_paciente.php?box=1');
        const data = await response.json();
        
        if (data.success) {
            resultsDiv.innerHTML = `
                <p>✅ <strong>API FUNCIONANDO</strong></p>
                <p>📋 Paciente: ${data.data.nombre_completo}</p>
                <p>🏥 Box: ${data.data.box}</p>
                <p><a href='grafica.html?box=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 IR A LA APLICACIÓN</a></p>
            `;
        } else {
            resultsDiv.innerHTML = `<p>❌ Error: ${data.message}</p>`;
        }
        
    } catch (error) {
        resultsDiv.innerHTML = `<p>❌ Error de conexión: ${error.message}</p>`;
    }
}

// Ejecutar test después de 2 segundos
setTimeout(testAPI, 2000);
</script>";
?>