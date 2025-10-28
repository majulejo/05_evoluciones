<?php
session_start();

// Destruir todas las variables de sesión
$_SESSION = [];

// Si se usa un manejador personalizado, también borra la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finalmente destruye la sesión
session_destroy();

// Redirigir a la página principal
header('Location: https://jolejuma.es/evolucion-uci/index.html'); 
exit;
?>