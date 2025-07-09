<?php
// ===== TEST ESPECÍFICO PARA API PACIENTES =====
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 TEST API PACIENTES</h1>";
echo "<pre>";

try {
    // 1. Verificar config.php
    echo "=== 1. VERIFICANDO CONFIG ===\n";
    if (file_exists('config.php')) {
        require_once 'config.php';
        echo "✅ config.php cargado\n";
        
        // Probar conexión directamente
        $pdo = obtenerConexionBD();
        echo "✅ Conexión establecida\n";
        
        // 2. Verificar tabla pacientes
        echo "\n=== 2. VERIFICANDO TABLA PACIENTES ===\n";
        $stmt = $pdo->query("DESCRIBE pacientes");
        $columns = $stmt->fetchAll();
        echo "📋 Estructura de tabla pacientes:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        
        // 3. Listar pacientes existentes
        echo "\n=== 3. PACIENTES EXISTENTES ===\n";
        $stmt = $pdo->query("SELECT box, nombre_completo, estado FROM pacientes ORDER BY box");
        $pacientes = $stmt->fetchAll();
        
        if (empty($pacientes)) {
            echo "❌ No hay pacientes en la BD\n";
            
            // Crear pacientes demo
            echo "🔧 Creando pacientes demo...\n";
            $insertSQL = "
                INSERT IGNORE INTO pacientes (box, nombre_completo, edad, peso, numero_historia, fecha_ingreso, estado) VALUES
                (1, 'Plinicio Ruiz Ruiz (DEMO)', 86, 100.00, '10000', '2025-01-07 17:09:44', 'activo'),
                (2, 'María García López (DEMO)', 72, 65.50, '10001', '2025-01-07 08:30:00', 'activo'),
                (3, 'Carlos Fernández Ruiz (DEMO)', 58, 80.25, '10002', '2025-01-07 14:15:00', 'activo')
            ";
            $pdo->exec($insertSQL);
            echo "✅ Pacientes demo creados\n";
            
            // Verificar de nuevo
            $stmt = $pdo->query("SELECT box, nombre_completo, estado FROM pacientes ORDER BY box");
            $pacientes = $stmt->fetchAll();
        }
        
        echo "👥 Pacientes encontrados:\n";
        foreach ($pacientes as $p) {
            echo "  Box {$p['box']}: {$p['nombre_completo']} ({$p['estado']})\n";
        }
        
        // 4. Probar consultas específicas
        echo "\n=== 4. PROBANDO CONSULTAS ===\n";
        for ($box = 1; $box <= 3; $box++) {
            $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE box = ? AND estado = 'activo'");
            $stmt->execute([$box]);
            $paciente = $stmt->fetch();
            
            if ($paciente) {
                echo "✅ Box $box: {$paciente['nombre_completo']}\n";
            } else {
                echo "❌ Box $box: Sin paciente activo\n";
            }
        }
        
        // 5. Simular llamada API
        echo "\n=== 5. SIMULANDO API CALL ===\n";
        
        // Simular $_GET
        $_GET['box'] = '1';
        
        // Ejecutar lógica de API
        $box = intval($_GET['box']);
        $sql = "SELECT * FROM pacientes WHERE box = :box AND estado = 'activo' LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['box' => $box]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            echo "✅ API Simulation Box 1: SUCCESS\n";
            echo "📋 Datos: " . json_encode($resultado, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ API Simulation Box 1: NO DATA\n";
        }
        
    } else {
        echo "❌ config.php no encontrado\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📄 Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== 6. RECOMENDACIONES ===\n";
echo "🔗 Después de este test, prueba:\n";
echo "  1. obtener_paciente.php?box=1\n";
echo "  2. obtener_paciente.php?box=2\n";
echo "  3. obtener_paciente.php?box=3\n";

echo "\n" . date('Y-m-d H:i:s') . " - Test completado\n";
echo "</pre>";
?>