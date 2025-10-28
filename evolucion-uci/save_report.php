<?php
/*
====================================
ARCHIVO: save_report.php
====================================
Guarda informes en la base de datos con detección automática de tabla
Compatible con informes_guardados e informes
Zona horaria: Madrid (Europe/Madrid)
*/

session_start();
date_default_timezone_set('Europe/Madrid');

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

try {
    // Configuración de base de datos
    $host = 'localhost';
    $dbname = 'u724879249_evolucion_uci';
    $username = 'u724879249_jamarquez06';
    $password = 'Farolill01.';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    // Leer datos JSON del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar datos requeridos
    if (!isset($data['id']) || !isset($data['user_id']) || !isset($data['box']) || !isset($data['datos'])) {
        throw new Exception('Faltan datos requeridos');
    }
    
    // Verificar que el user_id coincida con la sesión
    if ($data['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Usuario no autorizado');
    }
    
    // Detectar qué tabla usar (prioridad: informes_guardados)
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tableName = 'informes_guardados'; // Por defecto la preferida
    
    if (!in_array('informes_guardados', $tables) && in_array('informes', $tables)) {
        $tableName = 'informes';
    }
    
    // Verificar si el registro ya existe
    $checkStmt = $pdo->prepare("SELECT id FROM $tableName WHERE id = ?");
    $checkStmt->execute([$data['id']]);
    
    $timestamp = date('Y-m-d H:i:s');
    
    if ($checkStmt->rowCount() > 0) {
        // Actualizar registro existente
        if ($tableName === 'informes_guardados') {
            $stmt = $pdo->prepare("
                UPDATE $tableName 
                SET datos = ?, fecha_modificacion = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([
                json_encode($data['datos']),
                $data['id'],
                $data['user_id']
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE $tableName 
                SET datos = ?, timestamp = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([
                json_encode($data['datos']),
                $data['id'],
                $data['user_id']
            ]);
        }
        $action = 'actualizado';
    } else {
        // Insertar nuevo registro
        if ($tableName === 'informes_guardados') {
            $stmt = $pdo->prepare("
                INSERT INTO $tableName (id, user_id, box, datos, fecha_creacion) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $result = $stmt->execute([
                $data['id'],
                $data['user_id'],
                $data['box'],
                json_encode($data['datos'])
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO $tableName (id, box, fecha, timestamp, user_id, datos) 
                VALUES (?, ?, CURDATE(), CURRENT_TIMESTAMP, ?, ?)
            ");
            $result = $stmt->execute([
                $data['id'],
                $data['box'],
                $data['user_id'],
                json_encode($data['datos'])
            ]);
        }
        $action = 'guardado';
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'id' => $data['id'],
            'timestamp' => $timestamp,
            'table_used' => $tableName,
            'action' => $action,
            'message' => "Informe $action correctamente en tabla $tableName"
        ]);
    } else {
        throw new Exception('Error al guardar en base de datos');
    }
    
} catch (PDOException $e) {
    error_log("Error PDO en save_report.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log("Error general en save_report.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>