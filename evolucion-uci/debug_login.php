<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Login</h1>
    
    <div class="section">
        <h2>Estado de la Sesión</h2>
        <p><strong>Session ID:</strong> <?= session_id() ?></p>
        <p><strong>Session Status:</strong> <?= session_status() ?></p>
        <p><strong>Session Save Path:</strong> <?= session_save_path() ?></p>
        <p><strong>Session Name:</strong> <?= session_name() ?></p>
    </div>
    
    <div class="section">
        <h2>Variables de Sesión</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="section">
        <h2>Verificaciones</h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="success">
                ✅ $_SESSION['user_id'] está definido: <?= $_SESSION['user_id'] ?>
            </div>
        <?php else: ?>
            <div class="error">
                ❌ $_SESSION['user_id'] NO está definido
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true): ?>
            <div class="success">
                ✅ Usuario autenticado correctamente
            </div>
        <?php else: ?>
            <div class="error">
                ❌ Usuario NO autenticado
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="success">
                ✅ Usuario: <?= htmlspecialchars($_SESSION['usuario']) ?>
            </div>
        <?php else: ?>
            <div class="error">
                ❌ Nombre de usuario no disponible
            </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Información del Servidor</h2>
        <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
        <p><strong>User Agent:</strong> <?= $_SERVER['HTTP_USER_AGENT'] ?? 'No disponible' ?></p>
        <p><strong>IP:</strong> <?= $_SERVER['REMOTE_ADDR'] ?? 'No disponible' ?></p>
        <p><strong>Cookies enviadas:</strong></p>
        <pre><?php print_r($_COOKIE); ?></pre>
    </div>
    
    <div class="section">
        <h2>Pruebas</h2>
        <p><a href="app.php">🔗 Intentar acceder a app.php</a></p>
        <p><a href="index.html">🔗 Volver al login</a></p>
        <p><a href="logout.php">🔗 Cerrar sesión</a></p>
    </div>
    
    <div class="section">
        <h2>Test de Login</h2>
        <form method="post" action="debug_login.php">
            <input type="text" name="test_usuario" placeholder="Usuario" value="Jorudi">
            <input type="password" name="test_clave" placeholder="Contraseña" value="Alcaudete1">
            <button type="submit">Test Login</button>
        </form>
        
        <?php
        if ($_POST['test_usuario'] ?? false) {
            echo "<h3>Resultado del test:</h3>";
            $test_data = json_encode([
                'usuario' => $_POST['test_usuario'],
                'clave' => $_POST['test_clave']
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'login2.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $test_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "<p><strong>HTTP Code:</strong> $http_code</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
        ?>
    </div>
</body>
</html>