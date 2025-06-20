<?php
// get_report.php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
date_default_timezone_set('Europe/Madrid');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Validar parámetro id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de informe requerido']);
    exit();
}

$informe_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Conexión a la base de datos
    $host = 'localhost';
    $dbname = 'u724879249_evolucion_uci';
    $username = 'u724879249_jamarquez06';
    $password = 'Farolill01.';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Establecer zona horaria en MySQL
    $pdo->exec("SET time_zone = '+01:00'");

    // Buscar informe
    $sql = "SELECT box, neurologico, cardiovascular, respiratorio, renal, gastrointestinal, 
                   nutricional, termorregulacion, piel, otros, especial, datos
            FROM informes 
            WHERE id = :id AND user_id = :user_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $informe_id,
        ':user_id' => $user_id
    ]);

    $informe = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($informe) {
        // Crear array de datos desde los campos individuales
        $datos = [
            'neurologico' => $informe['neurologico'] ?? '',
            'cardiovascular' => $informe['cardiovascular'] ?? '',
            'respiratorio' => $informe['respiratorio'] ?? '',
            'renal' => $informe['renal'] ?? '',
            'gastrointestinal' => $informe['gastrointestinal'] ?? '',
            'nutricional' => $informe['nutricional'] ?? '',
            'termorregulacion' => $informe['termorregulacion'] ?? '',
            'piel' => $informe['piel'] ?? '',
            'otros' => $informe['otros'] ?? '',
            'especial' => $informe['especial'] ?? '',
            'firma' => '' // La firma se maneja por separado
        ];

        // Si hay un campo datos JSON, usarlo como respaldo
        if (!empty($informe['datos'])) {
            $datos_json = json_decode($informe['datos'], true);
            if ($datos_json) {
                $datos = array_merge($datos, $datos_json);
            }
        }

        echo json_encode([
            'success' => true, 
            'box' => (int)$informe['box'],
            'datos' => $datos
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Informe no encontrado']);
    }

} catch (PDOException $e) {
    error_log("Error en get_report.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log("Error general en get_report.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>