<?php
// test_api.php - ARCHIVO PARA PROBAR TU API DIRECTAMENTE

header('Content-Type: application/json; charset=utf-8');

// ✅ INCLUIR TU CONFIG
require_once 'config.php';

echo "<h2>🔍 Test de Conexión y APIs</h2>";

// 1. ✅ PROBAR CONEXIÓN
echo "<h3>1. Test de Conexión a BD</h3>";
try {
    $resultado = probarConexion();
    if ($resultado['success']) {
        echo "✅ Conexión exitosa: " . $resultado['message'] . "<br>";
    } else {
        echo "❌ Error de conexión: " . $resultado['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "<br>";
}

// 2. ✅ PROBAR ESTRUCTURA DE TABLA PACIENTES
echo "<h3>2. Estructura de tabla pacientes</h3>";
try {
    $pdo = obtenerConexionBD();
    
    $stmt = $pdo->query("DESCRIBE pacientes");
    $columnas = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
    foreach ($columnas as $columna) {
        echo "<tr>";
        echo "<td>" . $columna['Field'] . "</td>";
        echo "<td>" . $columna['Type'] . "</td>";
        echo "<td>" . $columna['Null'] . "</td>";
        echo "<td>" . $columna['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error consultando estructura: " . $e->getMessage() . "<br>";
}

// 3. ✅ MOSTRAR PACIENTES EXISTENTES
echo "<h3>3. Pacientes en BD</h3>";
try {
    $pdo = obtenerConexionBD();
    
    $stmt = $pdo->query("SELECT 
                            box, 
                            nombre_completo, 
                            estado, 
                            fecha_ingreso,
                            fecha_alta 
                        FROM pacientes 
                        ORDER BY box");
    $pacientes = $stmt->fetchAll();
    
    if (count($pacientes) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Box</th><th>Nombre</th><th>Estado</th><th>Ingreso</th><th>Alta</th></tr>";
        foreach ($pacientes as $paciente) {
            echo "<tr>";
            echo "<td>" . $paciente['box'] . "</td>";
            echo "<td>" . $paciente['nombre_completo'] . "</td>";
            echo "<td>" . $paciente['estado'] . "</td>";
            echo "<td>" . $paciente['fecha_ingreso'] . "</td>";
            echo "<td>" . ($paciente['fecha_alta'] ?: 'Sin alta') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No hay pacientes en la base de datos<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error consultando pacientes: " . $e->getMessage() . "<br>";
}

// 4. ✅ TEST API OBTENER_PACIENTE
echo "<h3>4. Test API obtener_paciente.php</h3>";

$boxesParaProbar = [1, 2, 3, 4, 5];

foreach ($boxesParaProbar as $box) {
    echo "<h4>Box $box:</h4>";
    
    try {
        $pdo = obtenerConexionBD();
        
        $sql = "SELECT * FROM pacientes WHERE box = ? AND estado = 'activo'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$box]);
        $paciente = $stmt->fetch();
        
        if ($paciente) {
            echo "✅ Paciente encontrado: " . $paciente['nombre_completo'] . " (Estado: " . $paciente['estado'] . ")<br>";
        } else {
            echo "⚪ Box vacío<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

// 5. ✅ VERIFICAR TABLAS RELACIONADAS
echo "<h3>5. Verificar tablas relacionadas</h3>";

$tablasRelacionadas = ['constantes_vitales', 'datos_oxigenacion'];

foreach ($tablasRelacionadas as $tabla) {
    try {
        $pdo = obtenerConexionBD();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
        $resultado = $stmt->fetch();
        
        echo "📊 Tabla $tabla: " . $resultado['total'] . " registros<br>";
        
        // Mostrar boxes con datos
        $stmt = $pdo->query("SELECT DISTINCT box FROM $tabla ORDER BY box");
        $boxes = $stmt->fetchAll();
        
        if (count($boxes) > 0) {
            $boxesConDatos = array_column($boxes, 'box');
            echo "   📦 Boxes con datos: " . implode(', ', $boxesConDatos) . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error con tabla $tabla: " . $e->getMessage() . "<br>";
    }
}

echo "<br><strong>🔧 Para corregir el problema:</strong><br>";
echo "1. Asegúrate de que el box 3 tenga un paciente con estado='activo'<br>";
echo "2. Reemplaza los archivos api/obtener_paciente.php y api/obtener_pacientes_activos.php<br>";
echo "3. Verifica que la ruta del config.php sea correcta en las APIs<br>";
?>

<script>
// Test directo desde JavaScript
console.log('🧪 Iniciando tests desde JavaScript...');

// Test la API corregida
fetch('api/obtener_paciente.php?box=3')
  .then(response => {
    console.log('📥 Status:', response.status);
    return response.text();
  })
  .then(text => {
    console.log('📄 Response raw:', text);
    try {
      const json = JSON.parse(text);
      console.log('✅ JSON parsed:', json);
    } catch (e) {
      console.error('❌ JSON inválido:', e);
    }
  })
  .catch(error => {
    console.error('❌ Error fetch:', error);
  });
</script>