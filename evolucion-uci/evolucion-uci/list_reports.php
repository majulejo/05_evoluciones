<?php
// list_reports.php
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

    // Obtener informes del usuario ordenados por fecha/hora descendente
    $sql = "SELECT id, box, fecha, timestamp 
            FROM informes 
            WHERE user_id = :user_id 
            ORDER BY fecha DESC, timestamp DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);

    $informes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear fechas para mostrar
    foreach ($informes as &$informe) {
        if ($informe['fecha'] && $informe['fecha'] !== '0000-00-00') {
            $fecha_formateada = date('d/m/Y', strtotime($informe['fecha']));
            $informe['fecha'] = $fecha_formateada;
        } else {
            $informe['fecha'] = 'Sin fecha';
        }
        
        if ($informe['timestamp']) {
            $hora_formateada = date('H:i', strtotime($informe['timestamp']));
            $informe['hora'] = $hora_formateada;
        } else {
            $informe['hora'] = 'Sin hora';
        }
    }

    echo json_encode(['success' => true, 'reports' => $informes]);

} catch (PDOException $e) {
    error_log("Error en list_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log("Error general en list_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>