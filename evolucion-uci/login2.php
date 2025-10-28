<?php
date_default_timezone_set('Europe/Madrid');

// Iniciar sesión
session_start();

// Configurar headers para JSON y cache
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

$usuario = trim($data['usuario'] ?? '');
$clave = trim($data['clave'] ?? '');

if (empty($usuario) || empty($clave)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos']));
}

try {
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                  'u724879249_jamarquez06', 'Farolill01.');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+02:00'");

    // Buscar usuario
    $stmt = $pdo->prepare("SELECT id, usuario, clave FROM usuarios WHERE usuario = ? LIMIT 1");
    $stmt->execute([$usuario]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($datos && password_verify($clave, $datos['clave'])) {
        // Login exitoso - regenerar ID de sesión
        session_regenerate_id(true);
        
        // Establecer variables de sesión
        $_SESSION['user_id'] = $datos['id'];
        $_SESSION['usuario'] = $datos['usuario'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Log de acceso exitoso
        error_log("Login exitoso - Usuario: {$usuario} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'user_id' => $datos['id'],
            'usuario' => $datos['usuario'],
            'message' => 'Acceso autorizado',
            'redirect' => 'app.php',
            'session_id' => session_id()
        ]);
        
    } else {
        // Login fallido
        error_log("Login fallido - Usuario: {$usuario} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Pausa para prevenir ataques de fuerza bruta
        sleep(1);
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos'
        ]);
    }

} catch (PDOException $e) {
    error_log("Error de BD en login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión con la base de datos'
    ]);

} catch (Exception $e) {
    error_log("Error general en login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>