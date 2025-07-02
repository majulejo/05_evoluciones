<?php
/*
====================================
ARCHIVO: delete_box_reports.php
====================================
Elimina todos los informes de un box específico
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
    
    if (!$data || !isset($data['box'])) {
        throw new Exception('Número de Box requerido');
    }
    
    $box = $data['box'];
    
    if (!is_numeric($box) || $box < 1 || $box > 12) {
        throw new Exception('Número de Box inválido');
    }
    
    $pdo = new PDO("mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4", 
                   'u724879249_jamarquez06', 'Farolill01.', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");
    
    $totalDeleted = 0;
    $tablesUsed = [];
    
    // Eliminar de informes_guardados
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM informes_guardados WHERE box = ? AND user_id = ?");
        $stmt->execute([$box, $_SESSION['user_id']]);
        $count1 = $stmt->fetch()['total'];
        
        if ($count1 > 0) {
            $deleteStmt = $pdo->prepare("DELETE FROM informes_guardados WHERE box = ? AND user_id = ?");
            $deleteStmt->execute([$box, $_SESSION['user_id']]);
            $deleted1 = $deleteStmt->rowCount();
            if ($deleted1 > 0) {
                $totalDeleted += $deleted1;
                $tablesUsed[] = "informes_guardados ($deleted1)";
            }
        }
    } catch (PDOException $e) {
        // Tabla no existe, continuar
    }
    
    // Eliminar de informes
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM informes WHERE box = ? AND user_id = ?");
        $stmt->execute([$box, $_SESSION['user_id']]);
        $count2 = $stmt->fetch()['total'];
        
        if ($count2 > 0) {
            $deleteStmt = $pdo->prepare("DELETE FROM informes WHERE box = ? AND user_id = ?");
            $deleteStmt->execute([$box, $_SESSION['user_id']]);
            $deleted2 = $deleteStmt->rowCount();
            if ($deleted2 > 0) {
                $totalDeleted += $deleted2;
                $tablesUsed[] = "informes ($deleted2)";
            }
        }
    } catch (PDOException $e) {
        // Tabla no existe, continuar
    }
    
    if ($totalDeleted > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Se eliminaron {$totalDeleted} informes del Box {$box}",
            'deleted_count' => $totalDeleted,
            'box' => $box,
            'tables_used' => implode(', ', $tablesUsed)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => "No hay informes en el Box {$box} para eliminar",
            'deleted_count' => 0,
            'box' => $box
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>