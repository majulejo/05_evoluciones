<?php
/*
====================================
ARCHIVO: delete_reports.php
====================================
Elimina un informe específico por ID
Busca y elimina de ambas tablas: informes_guardados e informes
Zona horaria: Madrid (Europe/Madrid)
*/

session_start();
date_default_timezone_set('Europe/Madrid');

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('ID de informe requerido');
    }
    
    $informe_id = $data['id'];
    
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                   'u724879249_jamarquez06', 'Farolill01.', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    // Buscar y eliminar en ambas tablas
    $deleted = false;
    $tableUsed = '';
    
    // Intentar eliminar de informes_guardados
    try {
        $stmt = $pdo->prepare("SELECT id FROM informes_guardados WHERE id = ? AND user_id = ?");
        $stmt->execute([$informe_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $deleteStmt = $pdo->prepare("DELETE FROM informes_guardados WHERE id = ? AND user_id = ?");
            $result = $deleteStmt->execute([$informe_id, $_SESSION['user_id']]);
            if ($result && $deleteStmt->rowCount() > 0) {
                $deleted = true;
                $tableUsed = 'informes_guardados';
            }
        }
    } catch (PDOException $e) {
        // Tabla no existe, continuar
    }
    
    // Si no se eliminó, intentar en informes
    if (!$deleted) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM informes WHERE id = ? AND user_id = ?");
            $stmt->execute([$informe_id, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                $deleteStmt = $pdo->prepare("DELETE FROM informes WHERE id = ? AND user_id = ?");
                $result = $deleteStmt->execute([$informe_id, $_SESSION['user_id']]);
                if ($result && $deleteStmt->rowCount() > 0) {
                    $deleted = true;
                    $tableUsed = 'informes';
                }
            }
        } catch (PDOException $e) {
            // Tabla no existe
        }
    }
    
    if ($deleted) {
        echo json_encode([
            'success' => true,
            'message' => 'Informe eliminado correctamente',
            'id' => $informe_id,
            'table_used' => $tableUsed
        ]);
    } else {
        throw new Exception('Informe no encontrado o no se pudo eliminar');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>