<?php
require_once 'api/config.php';

echo "<h2>Test Simple de Insulina</h2>";

// Test 1: Conectar a BD
try {
    $pdo = obtenerConexionBD();
    echo "<p>✅ Conexión a BD: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Conexión a BD: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Verificar tabla
try {
    $stmt = $pdo->prepare("DESCRIBE datos_oxigenacion");
    $stmt->execute();
    echo "<p>✅ Tabla datos_oxigenacion: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Tabla: " . $e->getMessage() . "</p>";
}

// Test 3: Función de cálculo
function calcularInsulinaTest($glucemia) {
    if ($glucemia < 150) {
        return ['unidades_sc' => 0, 'tipo' => 'NADA'];
    } elseif ($glucemia >= 151 && $glucemia <= 225) {
        return ['unidades_sc' => 6, 'tipo' => '6 U.I. s/c'];
    } elseif ($glucemia > 400) {
        return ['unidades_iv' => 2.4, 'tipo' => 'Perfusión I.V.'];
    }
    return ['unidades_sc' => 10, 'tipo' => 'Estándar'];
}

echo "<h3>Tests de Cálculo:</h3>";
echo "<p>Glucemia 120: " . json_encode(calcularInsulinaTest(120)) . "</p>";
echo "<p>Glucemia 200: " . json_encode(calcularInsulinaTest(200)) . "</p>";
echo "<p>Glucemia 450: " . json_encode(calcularInsulinaTest(450)) . "</p>";

// Test 4: Insertar dato de prueba
try {
    $fecha_actual = date('Y-m-d');
    $stmt = $pdo->prepare("
        INSERT INTO datos_oxigenacion 
        (numero_box, hora, fecha, insulina, insulina_iv, modo_insulina, tipo_insulina)
        VALUES (99, '14:00', ?, 14, 0, 'subcutanea', 'Test 14 U.I. s/c')
        ON DUPLICATE KEY UPDATE insulina = 14
    ");
    $stmt->execute([$fecha_actual]);
    echo "<p>✅ Inserción de prueba: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Inserción: " . $e->getMessage() . "</p>";
}

// Test 5: Leer datos
try {
    $stmt = $pdo->prepare("
        SELECT * FROM datos_oxigenacion 
        WHERE numero_box = 99 AND fecha = ?
    ");
    $stmt->execute([$fecha_actual]);
    $datos = $stmt->fetchAll();
    echo "<p>✅ Lectura: " . count($datos) . " registros encontrados</p>";
    if (count($datos) > 0) {
        echo "<pre>" . json_encode($datos[0], JSON_PRETTY_PRINT) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p>❌ Lectura: " . $e->getMessage() . "</p>";
}

echo "<p><strong>Si todos los tests están en ✅, la funcionalidad debería funcionar!</strong></p>";
?>