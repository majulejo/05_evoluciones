<?php
date_default_timezone_set('Europe/Madrid');

session_start();

// Limpiar cualquier sesión anterior
session_unset();
session_destroy();
session_start();

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

// Obtener datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'JSON inválido']));
}

$usuario = $data['usuario'] ?? '';
$clave = $data['clave'] ?? '';

if (empty($usuario) || empty($clave)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Campos vacíos']));
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                  'u724879249_jamarquez06', 'Farolill01.');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT id, clave FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($datos && password_verify($clave, $datos['clave'])) {
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
        
        // Establecer todas las variables de sesión necesarias
        $_SESSION['user_id'] = $datos['id'];
        $_SESSION['usuario'] = $usuario;
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Log de acceso exitoso
        error_log("Login exitoso para usuario: $usuario - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        echo json_encode([
            'success' => true,
            'user_id' => $datos['id'],
            'message' => 'Login exitoso',
            'redirect' => 'app.php'
        ]);
    } else {
        // Login fallido
        error_log("Intento de login fallido - Usuario: $usuario - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Pequeña pausa para prevenir ataques de fuerza bruta
        sleep(1);
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos.'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de base de datos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión BD'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error general: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor'
    ]);
}
?>