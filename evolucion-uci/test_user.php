<?php
date_default_timezone_set('Europe/Madrid');

// Test de usuario y contraseña
echo "<h1>Test de Usuario y Contraseña</h1>";

$usuario_test = 'Jorudi';
$clave_test = 'Alcaudete1';
$hash_esperado = '$2y$10$9kmB9CYzy1r0W.Im4nibSOsuix4BF81YBPjWxBPeTQp875Artlna';


echo "<h2>1. Verificar hash de contraseña</h2>";
echo "Contraseña a verificar: <strong>$clave_test</strong><br>";
echo "Hash en BD: <code>$hash_esperado</code><br>";

$verificacion = password_verify($clave_test, $hash_esperado);
echo "Resultado password_verify(): " . ($verificacion ? '<span style="color:green;">✅ CORRECTO</span>' : '<span style="color:red;">❌ INCORRECTO</span>') . "<br><br>";

echo "<h2>2. Verificar conexión a base de datos</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                  'u724879249_jamarquez06', 'Farolill01.');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión a BD exitosa<br><br>";
    
    echo "<h2>3. Buscar usuario en base de datos</h2>";
    $stmt = $pdo->prepare("SELECT id, usuario, clave FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario_test]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($datos) {
        echo "✅ Usuario encontrado en BD:<br>";
        echo "ID: " . $datos['id'] . "<br>";
        echo "Usuario: " . $datos['usuario'] . "<br>";
        echo "Hash en BD: <code>" . $datos['clave'] . "</code><br>";
        echo "Hash esperado: <code>" . $hash_esperado . "</code><br>";
        echo "¿Coinciden los hash?: " . ($datos['clave'] === $hash_esperado ? '<span style="color:green;">✅ SÍ</span>' : '<span style="color:red;">❌ NO</span>') . "<br><br>";
        
        echo "<h2>4. Verificar contraseña con hash de BD</h2>";
        $verificacion_bd = password_verify($clave_test, $datos['clave']);
        echo "password_verify() con hash de BD: " . ($verificacion_bd ? '<span style="color:green;">✅ CORRECTO</span>' : '<span style="color:red;">❌ INCORRECTO</span>') . "<br><br>";
        
    } else {
        echo "❌ Usuario NO encontrado en BD<br><br>";
    }
    
    echo "<h2>5. Listar todos los usuarios</h2>";
    $stmt_all = $pdo->prepare("SELECT id, usuario, LENGTH(clave) as hash_length FROM usuarios");
    $stmt_all->execute();
    $todos = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Longitud Hash</th></tr>";
    foreach ($todos as $user) {
        echo "<tr><td>" . $user['id'] . "</td><td>" . $user['usuario'] . "</td><td>" . $user['hash_length'] . "</td></tr>";
    }
    echo "</table><br>";
    
} catch (PDOException $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Generar hash nuevo (por si acaso)</h2>";
$nuevo_hash = password_hash($clave_test, PASSWORD_DEFAULT);
echo "Contraseña: <strong>$clave_test</strong><br>";
echo "Nuevo hash generado: <code>$nuevo_hash</code><br>";
echo "Verificación del nuevo hash: " . (password_verify($clave_test, $nuevo_hash) ? '<span style="color:green;">✅ OK</span>' : '<span style="color:red;">❌ FALLO</span>') . "<br>";

echo "<h2>7. SQL para insertar usuario con nuevo hash</h2>";
echo "<code style='background:#f0f0f0; padding:10px; display:block;'>";
echo "UPDATE usuarios SET clave = '$nuevo_hash' WHERE usuario = 'Jorudi';<br>";
echo "-- O si necesitas insertar desde cero:<br>";
echo "INSERT INTO usuarios (usuario, clave) VALUES ('Jorudi', '$nuevo_hash');";
echo "</code>";
?>