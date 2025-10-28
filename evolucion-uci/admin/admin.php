<?php

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ──────────────── CABECERAS NO-CACHE ────────────────
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// ──────────────── CONFIGURACIÓN ────────────────
define('ADMIN_TOKEN', 'faroladmin2024');
define('SESSION_TIMEOUT', 450); // Segundos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u724879249_evolucion_uci');
define('DB_USER', 'u724879249_jamarquez06');
define('DB_PASS', 'Farolill01.');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Conexión fallida: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ──────────────── FUNCIONES ÚTILES ────────────────
function login_form(string $msg = ''): void {
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Login Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"  rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">   
        <style>
            .input-group .input-group-text {
                background-color: transparent;
                border: none;
                padding: 0.5rem 0.75rem;
                cursor: pointer;
            }
            .input-group .input-group-text:hover {
                background-color: rgba(76, 175, 80, 0.1);
            }
        </style>
    </head>
    <body class="d-flex align-items-center justify-content-center vh-100" style="background-color: #f0f9f0;">
        <div class="container" style="max-width: 420px;">
            <div class="card shadow p-4" style="border-top: 4px solid #4CAF50;">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold" style="color: #2e7d32;">
                        <i class="bi bi-shield-lock me-2" style="color: #4CAF50;"></i>Acceso Admin
                    </h1>
                    <p class="small text-muted">Introduce el token de seguridad</p>
                </div>
                <?php if ($msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <form method="post">
                    <label class="form-label" for="token">Token de acceso</label>
                    <div class="input-group mb-3">
                        <input 
                            type="password" 
                            class="form-control" 
                            id="token" 
                            name="token" 
                            required 
                            autofocus 
                            aria-describedby="toggleToken"
                        >
                        <button 
                            class="input-group-text bg-transparent border-0" 
                            type="button" 
                            id="toggleToken" 
                            title="Mostrar u ocultar token"
                            style="color: #4CAF50;"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <button 
                        class="btn w-100 py-2 fw-bold" 
                        style="background-color: #4CAF50; color: white;"
                    >
                        <i class="bi bi-box-arrow-in-right  me-2"></i>Entrar
                    </button>
                </form>
            </div>
            <a href="logout_and_redirect.php" class="btn btn-outline-success mt-3 w-100">
                <i class="bi bi-box-arrow-left me-2"></i>Cerrar sesión
            </a>
        </div>

        <!-- Script anti-recarga -->
        <script>
        (function() {
            const isReload = performance.navigation.type === PerformanceNavigation.TYPE_RELOAD ||
                             performance.navigation.type === PerformanceNavigation.TYPE_BACK_FORWARD ||
                             performance.navigation.type === 255;
            if (isReload) {
                window.location.replace("https://jolejuma.es/evolucion-uci/index.html");   
            }
            document.addEventListener("keydown", function(e) {
                if ((e.key === "F5") || (e.ctrlKey && e.key === "F5")) {
                    window.location.href = "https://jolejuma.es/evolucion-uci/index.html";   
                }
            });
        })();
        document.getElementById("toggleToken").addEventListener("click", function () {
            const tokenInput = document.getElementById("token");
            const isPassword = tokenInput.type === "password";
            tokenInput.type = isPassword ? "text" : "password";
            this.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ──────────────── CONTROL DE SESIÓN MEJORADO ────────────────
// CAMBIO PRINCIPAL: Verificación más estricta de la sesión

// Verificar si existe una sesión activa
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    
    // 1. Verificar timeout de sesión
    if ((time() - ($_SESSION['login_time'] ?? 0)) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        // Limpiar cookies de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        login_form('La sesión ha expirado. Vuelve a introducir el token.');
    }
    
    // 2. Verificar integridad de la sesión - NUEVO
    // Verificar que tenga todos los datos necesarios de sesión
    if (!isset($_SESSION['login_time']) || 
        !isset($_SESSION['session_token']) || 
        $_SESSION['session_token'] !== md5(session_id() . ADMIN_TOKEN)) {
        
        // Si la sesión está incompleta o corrupta, forzar nuevo login
        session_unset();
        session_destroy();
        login_form('Sesión inválida. Por favor, inicia sesión nuevamente.');
    }
    
    // 3. Verificar que la sesión no haya sido iniciada desde otro lugar - NUEVO
    // Generar un fingerprint del navegador para mayor seguridad
    $current_fingerprint = md5(
        $_SERVER['HTTP_USER_AGENT'] ?? '' . 
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' .
        $_SERVER['REMOTE_ADDR'] ?? ''
    );
    
    if (!isset($_SESSION['browser_fingerprint']) || 
        $_SESSION['browser_fingerprint'] !== $current_fingerprint) {
        
        session_unset();
        session_destroy();
        login_form('Sesión iniciada desde otro dispositivo. Por favor, inicia sesión nuevamente.');
    }
    
} else {
    // No hay sesión activa, procesar login
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
        if ($_POST['token'] === ADMIN_TOKEN) {
            
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);
            
            // Establecer datos de sesión
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['session_token'] = md5(session_id() . ADMIN_TOKEN);
            
            // Guardar fingerprint del navegador
            $_SESSION['browser_fingerprint'] = md5(
                $_SERVER['HTTP_USER_AGENT'] ?? '' . 
                $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' .
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
            
            // Configurar cookie de sesión más segura
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Strict');
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        login_form('Token incorrecto');
    }
    login_form();
}

// Actualizar tiempo de sesión solo si todo está correcto
$_SESSION['login_time'] = time();

// ──────────────── LOGOUT MEJORADO ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    
    // Limpiar completamente la sesión
    $_SESSION = array();
    
    // Destruir cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sesión
    session_destroy();
    
    // Redireccionar
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Location: https://jolejuma.es/evolucion-uci/index.html');
    exit;
}
// ──────────────── ACCIONES: Crear/Eliminar Usuario ────────────────
$alert = '';
$alertType = 'success';

// Manejar logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    
    // Limpiar cookies de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Redireccionar al index
    header('Location: https://jolejuma.es/evolucion-uci/index.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    switch ($action) {
        case 'create':
            $usuario = trim($_POST['nuevo_usuario'] ?? '');
            $pass = trim($_POST['nuevo_clave'] ?? '');

            if (!$usuario || !$pass) {
                $alertType = 'danger';
                $alert = 'Debes rellenar usuario y contraseña.';
                break;
            }

            $check = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ?");
            $check->bind_param("s", $usuario);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                $alertType = 'danger';
                $alert = "El usuario <strong>$usuario</strong> ya existe.";
                break;
            }

            // Cifrar la contraseña
            $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

            // Insertar en la tabla usuarios
            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave) VALUES (?, ?)");
            $stmt->bind_param("ss", $usuario, $hashedPassword);

            if ($stmt->execute()) {
                // Eliminar cualquier entrada previa en user_password (por si acaso)
                $stmt_delete = $conn->prepare("DELETE FROM user_password WHERE usuario = ?");
                $stmt_delete->bind_param("s", $usuario);
                $stmt_delete->execute();
                $stmt_delete->close();
                
                // Insertar en la tabla user_password
                $stmt_temp = $conn->prepare("INSERT INTO user_password (usuario, clave) VALUES (?, ?)");
                $stmt_temp->bind_param("ss", $usuario, $pass);

                if ($stmt_temp->execute()) {
                    $alert = "Usuario creado correctamente: <strong>$usuario</strong>";
                } else {
                    $alertType = 'warning';
                    $alert = 'Usuario creado, pero error al guardar en tabla temporal: ' . $stmt_temp->error;
                }
                $stmt_temp->close();
            } else {
                $alertType = 'danger';
                $alert = 'Error al crear: ' . $stmt->error;
            }
            $stmt->close();
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                // Primero obtener el nombre de usuario
                $stmt_get = $conn->prepare("SELECT usuario FROM usuarios WHERE id = ?");
                $stmt_get->bind_param("i", $id);
                $stmt_get->execute();
                $stmt_get->bind_result($usuario_name);
                $stmt_get->fetch();
                $stmt_get->close();

                if ($usuario_name) {
                    // Eliminar de la tabla usuarios (principal)
                    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->bind_param("i", $id);

                    if ($stmt->execute()) {
                        // También eliminar de la tabla user_password (temporal/informativa)
                        $stmt_temp = $conn->prepare("DELETE FROM user_password WHERE usuario = ?");
                        $stmt_temp->bind_param("s", $usuario_name);
                        $stmt_temp->execute();
                        
                        $affected_temp = $stmt_temp->affected_rows;
                        $stmt_temp->close();
                        
                        if ($affected_temp > 0) {
                            $alert = "Usuario <strong>$usuario_name</strong> eliminado correctamente de ambas tablas.";
                        } else {
                            $alert = "Usuario <strong>$usuario_name</strong> eliminado de la tabla principal (no estaba en tabla temporal).";
                        }
                    } else {
                        $alertType = 'danger';
                        $alert = 'Error al eliminar: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $alertType = 'warning';
                    $alert = 'Usuario no encontrado.';
                }
            } else {
                $alertType = 'warning';
                $alert = 'ID no válido.';
            }
            break;
    }
}

// Consulta para obtener todos los usuarios con sus claves cifradas
$result = $conn->query("SELECT id, usuario, clave FROM usuarios ORDER BY id DESC");
$usuarios = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Consulta para obtener usuarios añadidos temporalmente
$temp_users_result = $conn->query("
    SELECT u.id, u.usuario, 
           COALESCE(
               (SELECT up.clave 
                FROM user_password up 
                WHERE up.usuario = u.usuario 
                ORDER BY up.id DESC 
                LIMIT 1), 
               'No disponible'
           ) as clave 
    FROM usuarios u 
    ORDER BY u.id DESC
");
$temp_users = $temp_users_result ? $temp_users_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AÑADE ESTA LÍNEA PARA BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background-color: #f0f9f0; color: #2e7d32; }
        .card-header { background-color: #4CAF50 !important; color: white !important; }
        .btn-success { background-color: #8BC34A !important; border-color: #8BC34A !important; }
        .btn-danger { background-color: #f44336 !important; border-color: #f44336 !important; }
        .btn-success:hover { background-color: #7CB342 !important; }
        .btn-danger:hover { background-color: #e53935 !important; }
        
        
        /* AÑADE estos estilos en la sección <style> de tu admin.php: */

/* Estilos para tabla responsive */
.table-responsive { 
    width: 90%; 
    margin: 0 auto;
    border-radius: 0;
}

.password-container {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    max-width: 300px;
}

.password-hash {
    font-size: 11px;
    padding: 4px 8px;
    background-color: #f8f9fa !important;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    word-break: break-all;
    line-height: 1.2;
    flex: 1;
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.copy-hash-btn {
    padding: 2px 6px;
    font-size: 10px;
    line-height: 1;
    min-width: 28px;
    height: 24px;
    flex-shrink: 0;
}

.copy-hash-btn:hover {
    background-color: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

/* Responsive para móviles */
@media (max-width: 768px) {
    .password-hash {
        font-size: 10px;
        max-width: 150px;
        padding: 3px 6px;
    }
    
    .copy-hash-btn {
        min-width: 24px;
        height: 20px;
        padding: 1px 4px;
        font-size: 9px;
    }
    
    /* Ocultar texto "Eliminar" en móvil */
    .btn-danger .d-none {
        display: none !important;
    }
}

@media (max-width: 576px) {
    .password-container {
        max-width: 120px;
    }
    
    .password-hash {
        max-width: 90px;
        font-size: 9px;
    }
    
    /* En móviles muy pequeños, mostrar hash en tooltips */
    .password-hash:hover::after {
        content: attr(data-full-hash);
        position: absolute;
        bottom: 100%;
        left: 0;
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 8px;
        border-radius: 4px;
        font-size: 10px;
        word-break: break-all;
        z-index: 1000;
        max-width: 300px;
        white-space: normal;
    }
}
        /* BOTON FLOTANTE */

/* Botón flotante de cerrar sesión - VERSIÓN VERDE */
#logoutBtn {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    
    background: linear-gradient(135deg, #4CAF50, #2e7d32);
    color: white;
    border: none;
    border-radius: 50px;
    
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Montserrat', sans-serif;
    
    display: flex;
    align-items: center;
    gap: 8px;
    
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    cursor: pointer;
    
    transition: all 0.3s ease;
    transform: translateY(0);
}

#logoutBtn:hover {
    background: linear-gradient(135deg, #66BB6A, #388E3C);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
}

#logoutBtn:active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(76, 175, 80, 0.3);
}

#logoutBtn i {
    font-size: 16px;
    transition: transform 0.3s ease;
}

#logoutBtn:hover i {
    transform: rotateZ(15deg);
}

/* Responsive - En móviles */
@media (max-width: 768px) {
    #logoutBtn {
        top: 15px;
        right: 15px;
        padding: 10px 16px;
        font-size: 13px;
    }
    
    #logoutBtn span {
        display: none; /* Ocultar texto en móvil */
    }
    
    #logoutBtn {
        border-radius: 50%; /* Hacer circular en móvil */
        width: 50px;
        height: 50px;
        padding: 0;
        justify-content: center;
    }
    
    #logoutBtn i {
        font-size: 18px;
        margin: 0;
    }
}

/* En pantallas muy pequeñas */
@media (max-width: 480px) {
    #logoutBtn {
        top: 10px;
        right: 10px;
        width: 45px;
        height: 45px;
    }
    
    #logoutBtn i {
        font-size: 16px;
    }
}

/* Animación de entrada */
@keyframes slideInFromRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

#logoutBtn {
    animation: slideInFromRight 0.5s ease-out;
}


/* ====== MEJORAS RESPONSIVE PARA TU ADMIN PANEL ====== */
/* Añade estos estilos a tu CSS existente sin cambiar nada más */

/* 1. MEJORAS GENERALES RESPONSIVE */
body { 
    background-color: #f0f9f0; 
    color: #2e7d32; 
    padding-top: 80px; /* Espacio para botón flotante */
}

/* 2. CONTENEDOR PRINCIPAL MÁS RESPONSIVE */
.container {
    padding-left: 15px;
    padding-right: 15px;
    max-width: 100%;
}

/* 3. TÍTULO PRINCIPAL RESPONSIVE */
.display-5 {
    font-size: clamp(1.8rem, 5vw, 3rem) !important;
    margin-bottom: 1rem !important;
}

.lead {
    font-size: clamp(0.9rem, 2.5vw, 1.1rem) !important;
}

/* 4. TARJETAS MÁS RESPONSIVE */
.card {
    margin-bottom: 1.5rem;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 90%; 
    margin-left: auto; 
    margin-right: auto; 
    margin-bottom: 2.5rem; /* En lugar de 1.5rem */
    border-radius: 10px;
    overflow: hidden; /* Para que los bordes redondeados funcionen */
}

.card-body.p-0 {
    padding: 0 !important;
}

.card.shadow-sm.mt-4 {
    margin-top: 3rem !important; /* Más separación entre tablas */
}


.card.shadow-sm.mt-4::before {
    content: '';
    position: absolute;
    top: -1.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #4CAF50, transparent);
}

.card.shadow-sm.mt-4 {
    position: relative;
    margin-top: 3rem !important;
}


.card-header {
    font-weight: 600;
    margin: 0;
    border-radius: 0; /* Sin bordes, los maneja la tarjeta padre */
    background: linear-gradient(135deg, #4CAF50, #66BB6A) !important;
    color: white !important;
    font-weight: 600;
    padding: 1rem 1.5rem;
}
}

.card-body {
    padding: 1.5rem;
}

/* 5. FORMULARIOS RESPONSIVE */
.form-control {
    padding: 0.75rem;
    font-size: 14px;
    border-radius: 6px;
}

.btn {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 6px;
}

/* 6. TABLAS MEJORADAS */
.table-responsive {
    width: 100% !important; /* Cambiar de 90% a 100% */
    margin: 0; /* Sin márgenes laterales */
    border-radius: 0; /* Sin bordes en la tabla, los tiene la tarjeta */
    box-shadow: none; /* La sombra ya la tiene la tarjeta */
}

.table th {
    background-color: #2e7d32 !important;
    color: white !important;
    border: none;
    padding: 1rem 0.75rem;
    font-weight: 600;
    font-size: 14px;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-color: #e8f5e8;
}

/* 7. BOTÓN FLOTANTE MEJORADO */
#logoutBtn {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    background: linear-gradient(135deg, #4CAF50, #2e7d32);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

#logoutBtn:hover {
    background: linear-gradient(135deg, #66BB6A, #388E3C);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
}

#logoutBtn i {
    font-size: 16px;
    transition: transform 0.3s ease;
}

#logoutBtn:hover i {
    transform: rotate(15deg);
}

/* 8. ALERTAS RESPONSIVE */
.alert {
    border-radius: 8px;
    border: none;
    font-weight: 500;
    margin-bottom: 1.5rem;
    max-width: 90%; 
    margin-left: auto; 
    margin-right: auto; 
}

/* ====== BREAKPOINTS RESPONSIVE ====== */

/* TABLETS (992px y menos) */
@media (max-width: 992px) {
    body {
        padding-top: 70px;
    }
    
    .container {
        padding-left: 12px;
        padding-right: 12px;
    }
    
    .table th, .table td {
        padding: 0.6rem 0.4rem;
        font-size: 13px;
    }
    
    .card-body {
        padding: 1.25rem;
    }
}

/* MÓVILES MEDIANOS (768px y menos) */
@media (max-width: 768px) {
    body {
        padding-top: 70px;
    }
    
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    /* Título más compacto */
    .display-5 {
        font-size: 1.8rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .lead {
        font-size: 1rem !important;
    }
    
    /* Formularios en móvil */
    .form-control {
        padding: 0.65rem;
        font-size: 16px; /* Evita zoom en iOS */
    }
    
    .btn {
        padding: 0.65rem 1.25rem;
        font-size: 14px;
    }
    
    /* Tablas más compactas */
    .table th, .table td {
        padding: 0.5rem 0.3rem;
        font-size: 12px;
    }
    
    /* Solo ocultar contraseña cifrada en la tabla de usuarios existentes */
    .card:not(:last-child) .table th:nth-child(3),
    .card:not(:last-child) .table td:nth-child(3) {
        display: none;
    }
    
    /* Para la tabla temporal, mantener Usuario y Contraseña visibles */
    #tempUsersTable th:nth-child(2), /* Usuario */
    #tempUsersTable td:nth-child(2),
    #tempUsersTable th:nth-child(3), /* Contraseña */
    #tempUsersTable td:nth-child(3) {
        display: table-cell !important;
    }
    
    /* Opcional: Ocultar solo Estado en móviles */
    #tempUsersTable th:nth-child(4),
    #tempUsersTable td:nth-child(4) {
        display: none;
    }
    
      /* Ocultar columna ID en TODAS las tablas */
    .table th:first-child,
    .table td:first-child {
        display: none !important;
    }
    
    /* Para usuarios existentes: ocultar también contraseña cifrada */
    .card:not(:last-child) .table th:nth-child(3),
    .card:not(:last-child) .table td:nth-child(3) {
        display: none;
    }
    
    /* Botón flotante circular en móvil */
    #logoutBtn span {
        display: none;
    }
    
    #logoutBtn {
        border-radius: 50%;
        width: 50px;
        height: 50px;
        padding: 0;
        justify-content: center;
        top: 15px;
        right: 15px;
    }
    
    #logoutBtn i {
        font-size: 18px;
        margin: 0;
    }
    
    /* Tarjetas más compactas */
    .card {
        margin-bottom: 1rem;
    }
    
    .card-header {
        padding: 0.75rem;
        font-size: 14px;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    /* Botones de acción más pequeños */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 11px;
    }
    
    /* Badges más pequeños */
    .badge {
        font-size: 10px;
        padding: 0.25rem 0.5rem;
    }
}

/* MÓVILES PEQUEÑOS (576px y menos) */
@media (max-width: 576px) {
    body {
        padding-top: 60px;
    }
    
    .container {
        padding-left: 8px;
        padding-right: 8px;
    }
    
    /* Título aún más compacto */
    .display-5 {
        font-size: 1.5rem !important;
    }
    
    .lead {
        font-size: 0.9rem !important;
    }
    
    /* Formularios ultra compactos */
    .form-control {
        padding: 0.5rem;
        font-size: 16px;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        font-size: 13px;
        width: 100%; /* Botones a ancho completo */
    }
    
    /* Tablas ultra compactas */
    .table th, .table td {
        padding: 0.4rem 0.2rem;
        font-size: 11px;
    }
    
    /* Para usuarios existentes: solo ID, Usuario y Acciones */
    .card:not(:last-child) .table th:nth-child(n+3):not(:last-child),
    .card:not(:last-child) .table td:nth-child(n+3):not(:last-child) {
        display: none;
    }
    
    /* Para tabla temporal: mantener ID, Usuario y Contraseña */
    /*#tempUsersTable th:nth-child(1),
    #tempUsersTable td:nth-child(1),*/
    #tempUsersTable th:nth-child(2), 
    #tempUsersTable td:nth-child(2),
    #tempUsersTable th:nth-child(3),
    #tempUsersTable td:nth-child(3) {
        display: table-cell !important;
    }
    
    #tempUsersTable th:nth-child(4),
    #tempUsersTable td:nth-child(4) {
        display: none;
    }
    
    
    
    /* Ocultar columna ID en AMBAS tablas en móviles pequeños */
    .table th:first-child,
    .table td:first-child {
        display: none;
    }
    
    /* Botón flotante más pequeño */
    #logoutBtn {
        width: 45px;
        height: 45px;
        top: 10px;
        right: 10px;
    }
    
    #logoutBtn i {
        font-size: 16px;
    }
    
    /* Tarjetas ultra compactas */
    .card-header {
        padding: 0.6rem;
        font-size: 13px;
    }
    
    .card-body {
        padding: 0.75rem;
    }
    
    /* Alertas más compactas */
    .alert {
        padding: 0.75rem;
        font-size: 13px;
        margin-bottom: 1rem;
    }
    
    /* Texto de información más pequeño */
    .small, small {
        font-size: 10px;
    }
}

/* MODO PAISAJE EN MÓVILES */
@media (max-width: 812px) and (orientation: landscape) {
    body {
        padding-top: 60px;
    }
    
    #logoutBtn {
        top: 8px;
        right: 12px;
        width: 40px;
        height: 40px;
    }
    
    .display-5 {
        font-size: 1.4rem !important;
        margin-bottom: 0.5rem !important;
    }
    
    .card {
        margin-bottom: 0.75rem;
    }
}

/* MEJORAS PARA ACCESIBILIDAD */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* MEJORES ESTADOS DE FOCUS */
.btn:focus,
.form-control:focus {
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.25) !important;
    outline: none !important;
}

/* MEJORAS PARA NOTIFICACIONES RESPONSIVE */
@keyframes slideInFromRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOutToRight {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100px);
    }
}

/* Notificaciones responsive */
.copy-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    background: #4CAF50;
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 1001;
    font-size: 14px;
    font-weight: 500;
    animation: slideInFromRight 0.3s ease-out;
    max-width: calc(100vw - 40px);
    word-wrap: break-word;
}

@media (max-width: 768px) {
    .copy-notification {
        top: 70px;
        right: 15px;
        left: 15px;
        font-size: 13px;
        padding: 10px 15px;
    }
}

@media (max-width: 576px) {
    .copy-notification {
        top: 60px;
        right: 10px;
        left: 10px;
        font-size: 12px;
        padding: 8px 12px;
    }
}

/* MEJORAS PARA LOS ICONOS Y BADGES */
.text-success {
    color: #2e7d32 !important;
}

.bg-success {
    background-color: #4CAF50 !important;
}

.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background-color: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

/* MEJOR ESPACIADO PARA ELEMENTOS */
.mb-5 {
    margin-bottom: 2rem !important;
}

@media (max-width: 768px) {
    .mb-5 {
        margin-bottom: 1.5rem !important;
    }
}

@media (max-width: 576px) {
    .mb-5 {
        margin-bottom: 1rem !important;
    }
}

/* ANIMACIÓN SUAVE PARA EL BOTÓN FLOTANTE */
#logoutBtn {
    animation: slideInFromRight 0.5s ease-out;
}

/* MEJORAS PARA LA TABLA DE USUARIOS TEMPORALES */
.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e8f5e8;
    margin: 0;
    border-radius: 0;
    padding: 1rem 1.5rem;
}

@media (max-width: 768px) {
    .card-footer {
        padding: 0.5rem 0.75rem;
        font-size: 11px;
    }
    .card {
        max-width: 95%;
    }
}

@media (max-width: 576px) {
    .card {
        max-width: 100%;
        margin-left: 5px;
        margin-right: 5px;
    }
    
    .card-header {
        padding: 0.75rem 1rem;
        font-size: 14px;
    }
    
    .card-footer {
        padding: 0.75rem 1rem;
        font-size: 12px;
    }
}

/* SOMBRAS CONSISTENTES */
.card {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(76, 175, 80, 0.15);
}

/* AJUSTE PARA TODAS LAS TARJETAS (formulario, usuarios existentes, usuarios temporales) */
.card.mb-4.shadow-sm,
.card.shadow-sm,
.card.shadow-sm.mt-4 {
    max-width: 90%;
    margin-left: auto;
    margin-right: auto;
}

/* Separación mejorada entre tarjetas */
.card.mb-4.shadow-sm {
    margin-bottom: 2.5rem !important;
}

.card.shadow-sm.mt-4 {
    margin-top: 2.5rem !important;
    margin-bottom: 2rem;
}

/* HOVER EFFECTS MEJORADOS */
.table tbody tr:hover {
    background-color: rgba(76, 175, 80, 0.05);
}

.btn-danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
}

.btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
}

/* RESPONSIVE PARA LOS BOTONES DE ORDENAMIENTO */
@media (max-width: 768px) {
    .card-header .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .card-header .d-flex > div {
        margin-top: 0.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .btn-sm {
        font-size: 11px;
        padding: 0.25rem 0.5rem;
    }
}

/* MEJORAS PARA INPUTS EN MÓVIL */
@media (max-width: 768px) {
    input[type="text"],
    input[type="password"] {
        font-size: 16px !important; /* Evita zoom automático en iOS */
    }
}

/* OPTIMIZACIÓN PARA PANTALLAS TÁCTILES */
@media (hover: none) and (pointer: coarse) {
    .btn {
        min-height: 44px; /* Tamaño mínimo recomendado para táctil */
    }
    
    .copy-hash-btn {
        min-width: 44px;
        min-height: 44px;
    }
}

/* ESTADO DE CARGA PARA EL BOTÓN FLOTANTE */
#logoutBtn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
}

#logoutBtn:disabled:hover {
    transform: none !important;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3) !important;
}
    </style>
</head>
<body>
<div class="container py-4">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold" style="color: #2e7d32;">
            <i class="bi bi-shield-lock me-2" style="color: #4CAF50;"></i>Panel de Administración
        </h1>
        
        <p class="lead text-muted">
            <i class="bi bi-people-fill me-1"></i> evolucion-uci
        </p>
        
        <!-- URL COMO TEXTO -->
        <div class="mb-2">
            <i class="bi bi-box-arrow-in-right me-2"></i><code class="text-muted small">https://jolejuma.es/evolucion-uci/index.html</code><i class="bi bi-box-arrow-in-left ms-2"></i>
        </div>
        
        
        
    </div>

    <?php if ($alert): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
            <?= $alert ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario para crear nuevos usuarios -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">Crear nuevo usuario</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" class="form-control" name="nuevo_usuario" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="text" class="form-control" name="nuevo_clave" required>
                </div>
                <button class="btn btn-success">Crear usuario</button>
            </form>
        </div>
    </div>


<!-- Botón flotante de cerrar sesión -->
<button id="logoutBtn" onclick="cerrarSesionAdmin()" title="Cerrar sesión">
    <i class="fas fa-sign-out-alt"></i>
    <span>Cerrar sesión</span>
</button>


    <!-- USUARIOS EXISTENTES: -->

<!-- Tabla de usuarios existentes -->
<div class="card shadow-sm">
    <div class="card-header">Usuarios existentes</div>
    <div class="card-body p-0">
        <!-- Wrapper responsive para la tabla -->
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th style="min-width: 60px;">ID</th>
                    <th style="min-width: 120px;">Usuario</th>
                    <th style="min-width: 200px;">Contraseña Cifrada</th>
                    <th style="min-width: 120px;">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td class="fw-bold text-success"><?= htmlspecialchars($u['usuario']) ?></td>
                        <td>
                            <div class="password-container">
                                <code class="password-hash"><?= htmlspecialchars($u['clave']) ?></code>
                                <button class="btn btn-sm btn-outline-secondary copy-hash-btn" 
                                        onclick="copyToClipboard('<?= htmlspecialchars($u['clave']) ?>')"
                                        title="Copiar hash">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-danger" 
                                        onclick="return confirm('¿Eliminar el usuario \'<?= htmlspecialchars($u['usuario']) ?>\'?\n\nEsto lo eliminará de ambas tablas.')"
                                        type="submit">
                                    <i class="fas fa-trash"></i>
                                    <span class="d-none d-md-inline ms-1">Eliminar</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


    <!-- USUARIOS TEMPORALES: -->


<!-- Tabla de usuarios añadidos temporalmente (Solo lectura) -->
<div class="card shadow-sm mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Usuarios añadidos temporalmente</span>
        <div>
            <button id="sortAlphabetical" class="btn btn-sm btn-outline-light me-2" title="Ordenar alfabéticamente">
                <i class="fas fa-sort-alpha-down"></i> A-Z
            </button>
            <small class="text-light"><i class="fas fa-info-circle"></i> Solo lectura - Se eliminan automáticamente</small>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (count($temp_users) > 0): ?>
            <table class="table table-striped mb-0" id="tempUsersTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Contraseña</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody id="tempUsersBody">
                <?php foreach ($temp_users as $tu): ?>
                    <tr data-original-order="<?= $tu['id'] ?>" data-username="<?= htmlspecialchars($tu['usuario']) ?>">
                        <td><?= $tu['id'] ?></td>
                        <td>
                            <i class="fas fa-user text-success me-2"></i>
                            <?= htmlspecialchars($tu['usuario']) ?>
                        </td>
                        <td>
                            <code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($tu['clave']) ?></code>
                        </td>
                        <td>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> Activo
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-info-circle me-2"></i>No hay usuarios temporales registrados
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer text-muted small">
        <i class="fas fa-lightbulb me-1"></i>
        <strong>Información:</strong> Esta tabla muestra las contraseñas en texto plano para referencia. 
        Para eliminar usuarios, usa la tabla "Usuarios existentes" de arriba.
        <span id="sortStatus" class="ms-3"></span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sortBtn = document.getElementById('sortAlphabetical');
    const tbody = document.getElementById('tempUsersBody');
    const sortStatus = document.getElementById('sortStatus');
    let isAlphabetical = false;
    let resetTimer = null;
    
    if (sortBtn && tbody) {
        sortBtn.addEventListener('click', function() {
            if (!isAlphabetical) {
                // Ordenar alfabéticamente
                sortAlphabetically();
                isAlphabetical = true;
                sortBtn.innerHTML = '<i class="fas fa-undo"></i> Original';
                sortBtn.title = 'Volver al orden original';
                sortStatus.innerHTML = '<i class="fas fa-sort-alpha-down text-success"></i> Ordenado alfabéticamente';
                
                // Timer para volver al orden original después de 2 minutos
                resetTimer = setTimeout(() => {
                    resetToOriginal();
                }, 120000); // 2 minutos
                
            } else {
                // Volver al orden original
                resetToOriginal();
            }
        });
    }
    
    function sortAlphabetically() {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const usernameA = a.getAttribute('data-username').toLowerCase();
            const usernameB = b.getAttribute('data-username').toLowerCase();
            return usernameA.localeCompare(usernameB);
        });
        
        // Limpiar y reordenar
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
    }
    
    function resetToOriginal() {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const orderA = parseInt(a.getAttribute('data-original-order'));
            const orderB = parseInt(b.getAttribute('data-original-order'));
            return orderB - orderA; // Orden descendente (más reciente primero)
        });
        
        // Limpiar y reordenar
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
        
        // Resetear botón y estado
        isAlphabetical = false;
        sortBtn.innerHTML = '<i class="fas fa-sort-alpha-down"></i> A-Z';
        sortBtn.title = 'Ordenar alfabéticamente';
        sortStatus.innerHTML = '<i class="fas fa-undo text-info"></i> Vuelto al orden original';
        
        // Limpiar timer
        if (resetTimer) {
            clearTimeout(resetTimer);
            resetTimer = null;
        }
        
        // Limpiar mensaje después de 3 segundos
        setTimeout(() => {
            sortStatus.textContent = '';
        }, 3000);
    }
});

//BOTON FLOTANTE CERRAR SESIÓN 


    function cerrarSesionAdmin() {
    if (confirm('¿Estás seguro de que quieres cerrar la sesión?')) {
        // Mostrar indicador de carga
        const btn = document.getElementById('logoutBtn');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Cerrando...</span>';
        btn.disabled = true;
        
        // Simular pequeña pausa para feedback visual
        setTimeout(() => {
            // Crear formulario invisible para cerrar sesión
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'logout';
            input.value = '1';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }, 500);
    }
}

// Efectos adicionales al scroll (opcional)
let lastScrollTop = 0;
window.addEventListener('scroll', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    // Efecto sutil de opacidad al hacer scroll
    if (scrollTop > lastScrollTop && scrollTop > 100) {
        // Scrolling down
        logoutBtn.style.opacity = '0.8';
    } else {
        // Scrolling up
        logoutBtn.style.opacity = '1';
    }
    lastScrollTop = scrollTop;
});

// AÑADE esta función en tu sección de JavaScript:

function copyToClipboard(text) {
    // Método moderno para navegadores compatibles
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopyFeedback('Hash copiado al portapapeles ✅');
        }).catch(err => {
            console.error('Error al copiar: ', err);
            fallbackCopy(text);
        });
    } else {
        // Método fallback para navegadores antiguos
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopyFeedback('Hash copiado al portapapeles ✅');
        } else {
            showCopyFeedback('Error al copiar ❌');
        }
    } catch (err) {
        console.error('Error al copiar: ', err);
        showCopyFeedback('Error al copiar ❌');
    }
    
    document.body.removeChild(textArea);
}

function showCopyFeedback(message) {
    // Crear notificación temporal
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: #4CAF50;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 1001;
        font-size: 14px;
        font-weight: 500;
        animation: slideInFromRight 0.3s ease-out;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Eliminar después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutToRight 0.3s ease-in forwards';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 2700);
}

// Añadir animaciones CSS para las notificaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutToRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(style);
</script>

</body>
</html>