<?php
/**
 * admin.php – Panel de administración para usuarios (estilo verde)
 * -----------------------------------------------------------------------------
 * ▸ Acceso restringido con token único: faroladmin2024
 * ▸ Tabla mínima: id (AI-PK), usuario (UNIQUE), clave (hash)
 * ▸ Sesión caduca tras 120 segundos de inactividad
 * ▸ Estilo en tonos verdes consistentes
 * ▸ Redirige automáticamente al índice si se recarga la página
 * -----------------------------------------------------------------------------
 * Requisitos: PHP ≥ 7.4 + mysqli
 * -----------------------------------------------------------------------------
 */
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ──────────────── CABECERAS NO-CACHE ────────────────
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
// ──────────────── CONFIGURACIÓN ────────────────
define('ADMIN_TOKEN', 'faroladmin2024');
define('SESSION_TIMEOUT', 120); // Segundos
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
// ──────────────── CONTROL DE SESIÓN ────────────────
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ((time() - ($_SESSION['login_time'] ?? 0)) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        login_form('La sesión ha expirado. Vuelve a introducir el token.');
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
        if ($_POST['token'] === ADMIN_TOKEN) {
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        login_form('Token incorrecto');
    }
    login_form();
}
$_SESSION['login_time'] = time();
// ──────────────── ACCIONES: Crear/Eliminar Usuario ────────────────
$alert = '';
$alertType = 'success';
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
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave) VALUES (?, ?)");
            $stmt->bind_param("ss", $usuario, $hash);
            if ($stmt->execute()) {
                $alert = "Usuario creado correctamente: <strong>$usuario</strong>";
            } else {
                $alertType = 'danger';
                $alert = 'Error al crear: ' . $stmt->error;
            }
            $stmt->close();
            break;
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $alert = "Usuario eliminado correctamente.";
                } else {
                    $alertType = 'danger';
                    $alert = 'Error al eliminar: ' . $stmt->error;
                }
                $stmt->close();
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
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"  rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">  
    <style>
        body { background-color: #f0f9f0; color: #2e7d32; }
        .card-header { background-color: #4CAF50 !important; color: white !important; }
        .btn-success { background-color: #8BC34A !important; border-color: #8BC34A !important; }
        .btn-danger { background-color: #f44336 !important; border-color: #f44336 !important; }
        .btn-success:hover { background-color: #7CB342 !important; }
        .btn-danger:hover { background-color: #e53935 !important; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold" style="color: #2e7d32;">
            <i class="bi bi-shield-lock me-2" style="color: #4CAF50;"></i>Panel de Administración
        </h1>
        <p class="lead text-muted">
            <i class="bi bi-people-fill me-1"></i> Gestiona los usuarios del sistema
        </p>
    </div>
    <?php if ($alert): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
            <?= $alert ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
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
    <div class="card shadow-sm">
        <div class="card-header">Usuarios existentes</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr><th>ID</th><th>Usuario</th><th>Contraseña Cifrada</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['usuario']) ?></td>
                        <td><?= htmlspecialchars($u['clave']) ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este usuario?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4 text-center">
        <a href="logout_and_redirect.php" 
           class="btn btn-outline-success mt-3 w-100 d-flex align-items-center justify-content-center">
            <i class="bi bi-house-door me-2"></i>Volver al inicio
        </a>
    </div>
</div>
<script>
function actualizarHashPreview() {
    const pass = document.getElementById("nuevo_clave").value;
    if (!pass) return;
    fetch("hash_preview.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "pass=" + encodeURIComponent(pass)
    }).then(res => res.text()).then(hash => document.getElementById("preview_hash").value = hash);
}
(function() {
    const isReload = performance.navigation.type === PerformanceNavigation.TYPE_RELOAD ||
                     performance.navigation.type === PerformanceNavigation.TYPE_BACK_FORWARD ||
                     performance.navigation.type === 255;
    if (isReload) {
        window.location.replace("https://jolejuma.es/evolucion-uci/index.html");  
    }
})();
</script>
</body>
</html>