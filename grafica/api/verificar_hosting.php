<?php
// ===== SCRIPT DE VERIFICACIÓN COMPLETA PARA HOSTING =====
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo "<h1>🔍 VERIFICACIÓN COMPLETA DEL HOSTING</h1>";
echo "<pre>";

// ✅ 1. VERIFICAR CONFIG.PHP
echo "=== 1. VERIFICANDO CONFIG.PHP ===\n";
if (file_exists('config.php')) {
    require_once 'config.php';
    echo "✅ config.php encontrado\n";
    echo "🔧 Host: " . DB_HOST . "\n";
    echo "🗄️ BD: " . DB_NAME . "\n";
    echo "👤 Usuario: " . DB_USER . "\n";
    
    // Probar conexión
    try {
        $pdo = obtenerConexionBD();
        echo "✅ Conexión a BD exitosa\n";
        
        // Información de la BD
        $stmt = $pdo->query("SELECT DATABASE() as db_name, VERSION() as version");
        $info = $stmt->fetch();
        echo "📊 BD Activa: " . $info['db_name'] . "\n";
        echo "🔢 Versión MySQL: " . $info['version'] . "\n";
        
    } catch (Exception $e) {
        echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ config.php NO encontrado\n";
}

echo "\n=== 2. VERIFICANDO TABLAS ===\n";
try {
    $pdo = obtenerConexionBD();
    
    // Listar todas las tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Tablas existentes (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Verificar tablas específicas necesarias
    $requiredTables = ['pacientes', 'constantes_vitales', 'datos_oxigenacion'];
    
    echo "\n🔍 Verificando tablas requeridas:\n";
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "✅ $table: EXISTE\n";
            
            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch();
            echo "   📊 Registros: " . $count['count'] . "\n";
            
        } else {
            echo "❌ $table: NO EXISTE\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error verificando tablas: " . $e->getMessage() . "\n";
}

echo "\n=== 3. CREANDO TABLAS FALTANTES ===\n";
try {
    $pdo = obtenerConexionBD();
    
    // 🏥 TABLA PACIENTES
    $stmt = $pdo->query("SHOW TABLES LIKE 'pacientes'");
    if ($stmt->rowCount() === 0) {
        echo "🔧 Creando tabla 'pacientes'...\n";
        $sql = "
            CREATE TABLE pacientes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                box INT NOT NULL UNIQUE,
                nombre_completo VARCHAR(255) NOT NULL,
                edad INT NOT NULL,
                peso DECIMAL(5,2) NULL,
                numero_historia VARCHAR(50) NOT NULL,
                fecha_ingreso DATETIME NOT NULL,
                estado ENUM('activo', 'alta') DEFAULT 'activo',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_box (box),
                INDEX idx_estado (estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        $pdo->exec($sql);
        echo "✅ Tabla 'pacientes' creada\n";
        
        // Insertar datos de prueba
        $insertSQL = "
            INSERT INTO pacientes (box, nombre_completo, edad, peso, numero_historia, fecha_ingreso) VALUES
            (1, 'Plinicio Ruiz Ruiz (DEMO)', 86, 100.00, '10000', '2025-01-06 17:09:44'),
            (2, 'María García López (DEMO)', 72, 65.50, '10001', '2025-01-06 08:30:00'),
            (3, 'Carlos Fernández Ruiz (DEMO)', 58, 80.25, '10002', '2025-01-06 14:15:00')
        ";
        $pdo->exec($insertSQL);
        echo "✅ Datos de prueba insertados en 'pacientes'\n";
    } else {
        echo "✅ Tabla 'pacientes' ya existe\n";
    }
    
    // 💓 TABLA CONSTANTES VITALES
    $stmt = $pdo->query("SHOW TABLES LIKE 'constantes_vitales'");
    if ($stmt->rowCount() === 0) {
        echo "🔧 Creando tabla 'constantes_vitales'...\n";
        $sql = "
            CREATE TABLE constantes_vitales (
                id INT AUTO_INCREMENT PRIMARY KEY,
                box INT NOT NULL,
                hoja INT NOT NULL DEFAULT 1,
                hora TIME NOT NULL,
                fecha DATE NOT NULL DEFAULT (CURDATE()),
                fr INT NULL COMMENT 'Frecuencia respiratoria',
                temperatura DECIMAL(4,1) NULL COMMENT 'Temperatura corporal',
                fc INT NULL COMMENT 'Frecuencia cardíaca',
                ta_sistolica INT NULL COMMENT 'Tensión arterial sistólica',
                ta_diastolica INT NULL COMMENT 'Tensión arterial diastólica',
                saturacion INT NULL COMMENT 'Saturación de oxígeno',
                glucemia INT NULL COMMENT 'Glucemia en mg/dL',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_box_hoja_hora (box, hoja, hora),
                UNIQUE KEY unique_box_hoja_hora (box, hoja, hora)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        $pdo->exec($sql);
        echo "✅ Tabla 'constantes_vitales' creada\n";
        
        // Insertar datos de prueba
        $insertSQL = "
            INSERT INTO constantes_vitales (box, hoja, hora, fr, temperatura, fc, ta_sistolica, ta_diastolica, saturacion, glucemia) VALUES
            (1, 1, '08:00', 18, 36.5, 75, 120, 80, 98, 110),
            (1, 1, '12:00', 20, 36.8, 82, 125, 85, 97, 130),
            (1, 1, '16:00', 19, 37.1, 88, 130, 90, 96, 145),
            (1, 1, '20:00', 22, 37.2, 90, 135, 95, 95, 160),
            (2, 1, '10:00', 16, 36.2, 70, 115, 75, 99, 105),
            (3, 1, '09:00', 21, 37.0, 85, 140, 85, 94, 150)
        ";
        $pdo->exec($insertSQL);
        echo "✅ Datos de prueba insertados en 'constantes_vitales'\n";
    } else {
        echo "✅ Tabla 'constantes_vitales' ya existe\n";
    }
    
    // 🫁 TABLA DATOS OXIGENACIÓN
    $stmt = $pdo->query("SHOW TABLES LIKE 'datos_oxigenacion'");
    if ($stmt->rowCount() === 0) {
        echo "🔧 Creando tabla 'datos_oxigenacion'...\n";
        $sql = "
            CREATE TABLE datos_oxigenacion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                box INT NOT NULL,
                hoja INT NOT NULL DEFAULT 1,
                hora TIME NOT NULL,
                fecha DATE NOT NULL DEFAULT (CURDATE()),
                p_neumo INT NULL COMMENT 'Presión neumática 0-100',
                oxigenacion VARCHAR(20) NULL COMMENT 'VMI, VMNI, O2, Sin especificar',
                saturacion INT NULL COMMENT 'Saturación O2 % (sincronizada)',
                eva_escid VARCHAR(50) NULL COMMENT 'EVA/ESCID/RASS combinados',
                rass INT NULL COMMENT 'RASS separado (-5 a +4)',
                glucemia INT NULL COMMENT 'Glucemia mg/dL (sincronizada)',
                insulina VARCHAR(100) NULL COMMENT 'Dosis de insulina',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_box_hoja_hora (box, hoja, hora),
                UNIQUE KEY unique_box_hoja_hora (box, hoja, hora)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        $pdo->exec($sql);
        echo "✅ Tabla 'datos_oxigenacion' creada\n";
        
        // Insertar datos de prueba
        $insertSQL = "
            INSERT INTO datos_oxigenacion (box, hoja, hora, eva_escid, oxigenacion, p_neumo, insulina) VALUES
            (1, 1, '08:00', '5', 'VMI', 80, '6 U.I.'),
            (1, 1, '12:00', '7/+', 'O2', 60, '10 U.I.'),
            (1, 1, '16:00', '3/-2', 'VMNI', 70, '15 U.I.'),
            (1, 1, '20:00', '8/+/-1', 'VMI', 85, '20 U.I.'),
            (2, 1, '10:00', '4', 'O2', 50, NULL),
            (2, 1, '14:00', '6/+', 'VMNI', 65, '8 U.I.'),
            (3, 1, '09:00', '2', 'O2', 40, NULL),
            (3, 1, '15:00', '5/-1', 'VMI', 75, '12 U.I.')
        ";
        $pdo->exec($insertSQL);
        echo "✅ Datos de prueba insertados en 'datos_oxigenacion'\n";
    } else {
        echo "✅ Tabla 'datos_oxigenacion' ya existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creando tablas: " . $e->getMessage() . "\n";
}

echo "\n=== 4. VERIFICANDO ARCHIVOS API ===\n";
$apiFiles = [
    'config.php',
    'obtener_paciente.php',
    'obtener_constantes.php',
    'guardar_constantes.php', 
    'obtener_oxigenacion.php',
    'guardar_oxigenacion.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "✅ $file ($size bytes, mod: $modified)\n";
    } else {
        echo "❌ $file NO ENCONTRADO\n";
    }
}

echo "\n=== 5. PROBANDO APIs ===\n";
try {
    // Probar obtener_paciente.php
    echo "🔍 Probando obtener_paciente.php?box=1...\n";
    $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/obtener_paciente.php?box=1';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "✅ obtener_paciente.php: FUNCIONA\n";
            echo "   📋 Paciente: " . ($data['data']['nombre_completo'] ?? 'N/A') . "\n";
        } else {
            echo "⚠️ obtener_paciente.php: Respuesta sin éxito\n";
        }
    } else {
        echo "❌ obtener_paciente.php: Error de conexión\n";
    }
    
    // Probar obtener_constantes.php
    echo "🔍 Probando obtener_constantes.php?box=1...\n";
    $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/obtener_constantes.php?box=1';
    
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "✅ obtener_constantes.php: FUNCIONA\n";
            echo "   📊 Registros: " . $data['count'] . "\n";
        } else {
            echo "⚠️ obtener_constantes.php: Respuesta sin éxito\n";
        }
    } else {
        echo "❌ obtener_constantes.php: Error de conexión\n";
    }
    
    // Probar obtener_oxigenacion.php
    echo "🔍 Probando obtener_oxigenacion.php?box=1...\n";
    $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/obtener_oxigenacion.php?box=1';
    
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "✅ obtener_oxigenacion.php: FUNCIONA\n";
            echo "   🫁 Registros: " . $data['count'] . "\n";
        } else {
            echo "⚠️ obtener_oxigenacion.php: Respuesta sin éxito\n";
        }
    } else {
        echo "❌ obtener_oxigenacion.php: Error de conexión\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error probando APIs: " . $e->getMessage() . "\n";
}

echo "\n=== 6. RESUMEN FINAL ===\n";
try {
    $pdo = obtenerConexionBD();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pacientes");
    $pacientes = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM constantes_vitales");
    $constantes = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM datos_oxigenacion");
    $oxigenacion = $stmt->fetch()['count'];
    
    echo "📊 ESTADÍSTICAS:\n";
    echo "  👥 Pacientes: $pacientes\n";
    echo "  💓 Constantes vitales: $constantes\n";
    echo "  🫁 Datos oxigenación: $oxigenacion\n";
    
    if ($pacientes > 0 && $constantes > 0 && $oxigenacion > 0) {
        echo "\n🎉 ¡CONFIGURACIÓN COMPLETA!\n";
        echo "✅ La aplicación debería funcionar correctamente\n";
        echo "🔗 Puedes acceder a: grafica.html?box=1\n";
    } else {
        echo "\n⚠️ Configuración incompleta\n";
        echo "❌ Faltan datos en algunas tablas\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error en resumen final: " . $e->getMessage() . "\n";
}

echo "\n" . date('Y-m-d H:i:s') . " - Verificación completada\n";
echo "</pre>";
?>