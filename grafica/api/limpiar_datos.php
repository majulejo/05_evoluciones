
    
    
    <?php
/**
 * SCRIPT PARA LIMPIAR COMPLETAMENTE LA BASE DE DATOS
 * Elimina todos los pacientes, constantes y datos de oxigenación
 * ¡USAR CON PRECAUCIÓN! - ELIMINA TODOS LOS DATOS
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Configuración de base de datos
    $host = 'localhost';
    $dbname = 'u724879249_data';
    $username = 'u724879249_data';
    $password = 'Farolill0.1';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // ====== VERIFICAR CONTRASEÑA DE SEGURIDAD ======
    $input = json_decode(file_get_contents('php://input'), true);
    $password_seguridad = $input['password'] ?? '';
    
    // Cambiar esta contraseña por una más segura
    if ($password_seguridad !== 'LIMPIAR_TODO_2025') {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Contraseña de seguridad incorrecta'
        ]);
        exit;
    }
    
    // ====== COMENZAR TRANSACCIÓN ======
    $pdo->beginTransaction();
    
    echo json_encode(['status' => 'Iniciando limpieza...']);
    flush();
    
    // ====== 1. ELIMINAR DATOS DE OXIGENACIÓN ======
    $stmt1 = $pdo->prepare("DELETE FROM datos_oxigenacion");
    $stmt1->execute();
    $oxigenacion_eliminados = $stmt1->rowCount();
    
    // Resetear AUTO_INCREMENT
    $pdo->exec("ALTER TABLE datos_oxigenacion AUTO_INCREMENT = 1");
    
    // ====== 2. ELIMINAR CONSTANTES VITALES ======
    $stmt2 = $pdo->prepare("DELETE FROM constantes_vitales");
    $stmt2->execute();
    $constantes_eliminadas = $stmt2->rowCount();
    
    // Resetear AUTO_INCREMENT
    $pdo->exec("ALTER TABLE constantes_vitales AUTO_INCREMENT = 1");
    
    // ====== 3. ELIMINAR PACIENTES ======
    $stmt3 = $pdo->prepare("DELETE FROM pacientes");
    $stmt3->execute();
    $pacientes_eliminados = $stmt3->rowCount();
    
    // Resetear AUTO_INCREMENT
    $pdo->exec("ALTER TABLE pacientes AUTO_INCREMENT = 1");
    
    // ====== 4. VERIFICAR Y ELIMINAR TABLAS OPCIONALES ======
    $historial_eliminados = 0;
    $sesiones_eliminadas = 0;
    
    // Verificar si existe tabla historial
    $stmt_check_historial = $pdo->query("SHOW TABLES LIKE 'historial'");
    if ($stmt_check_historial->rowCount() > 0) {
        try {
            $stmt4 = $pdo->prepare("DELETE FROM historial");
            $stmt4->execute();
            $historial_eliminados = $stmt4->rowCount();
            $pdo->exec("ALTER TABLE historial AUTO_INCREMENT = 1");
        } catch (PDOException $e) {
            error_log("Error eliminando historial: " . $e->getMessage());
        }
    }
    
    // Verificar si existe tabla sesiones
    $stmt_check_sesiones = $pdo->query("SHOW TABLES LIKE 'sesiones'");
    if ($stmt_check_sesiones->rowCount() > 0) {
        try {
            $stmt5 = $pdo->prepare("DELETE FROM sesiones");
            $stmt5->execute();
            $sesiones_eliminadas = $stmt5->rowCount();
            $pdo->exec("ALTER TABLE sesiones AUTO_INCREMENT = 1");
        } catch (PDOException $e) {
            error_log("Error eliminando sesiones: " . $e->getMessage());
        }
    }
    
    // ====== VERIFICAR QUE TODO ESTÁ VACÍO ======
    $verificacion = [];
    
    $stmt_check1 = $pdo->query("SELECT COUNT(*) as count FROM pacientes");
    $verificacion['pacientes'] = $stmt_check1->fetch()['count'];
    
    $stmt_check2 = $pdo->query("SELECT COUNT(*) as count FROM constantes_vitales");
    $verificacion['constantes_vitales'] = $stmt_check2->fetch()['count'];
    
    $stmt_check3 = $pdo->query("SELECT COUNT(*) as count FROM datos_oxigenacion");
    $verificacion['datos_oxigenacion'] = $stmt_check3->fetch()['count'];
    
    // ====== CONFIRMAR TRANSACCIÓN ======
    $pdo->commit();
    
    // ====== RESPUESTA EXITOSA ======
    echo json_encode([
        'success' => true,
        'message' => 'Base de datos limpiada completamente',
        'eliminados' => [
            'pacientes' => $pacientes_eliminados,
            'constantes_vitales' => $constantes_eliminadas,
            'datos_oxigenacion' => $oxigenacion_eliminados,
            'historial' => $historial_eliminados,
            'sesiones' => $sesiones_eliminadas
        ],
        'verificacion' => $verificacion,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    // Rollback en caso de error
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en base de datos: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage()
    ]);
}
?>