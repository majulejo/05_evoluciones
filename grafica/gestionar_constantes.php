<?php
// gestionar_constantes.php - Gestionar constantes vitales

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Configuración de base de datos directa
$host = 'localhost';
$dbname = 'u724879249_data';
$username = 'u724879249_data';
$password = 'Farolill0.1';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit;
}

// Crear tabla de constantes vitales si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS constantes_vitales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero_box INT NOT NULL,
            hora VARCHAR(5) NOT NULL,
            fecha_hoja DATE NOT NULL,
            fr INT DEFAULT NULL,
            temperatura DECIMAL(3,1) DEFAULT NULL,
            fc INT DEFAULT NULL,
            ta_sistolica INT DEFAULT NULL,
            ta_diastolica INT DEFAULT NULL,
            sat_o2 INT DEFAULT NULL,
            glucemia INT DEFAULT NULL,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_box_hora_fecha (numero_box, hora, fecha_hoja),
            INDEX idx_box_fecha (numero_box, fecha_hoja)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    error_log("Error al crear tabla constantes_vitales: " . $e->getMessage());
}

// Obtener datos del POST
$accion = $_POST['accion'] ?? '';
$numeroBox = intval($_POST['box'] ?? 0);

// Validar número de box
if ($numeroBox < 1 || $numeroBox > 12) {
    echo json_encode([
        'success' => false,
        'error' => 'Número de box inválido'
    ]);
    exit;
}

try {
    switch ($accion) {
        case 'guardar_constantes':
            $hora = $_POST['hora'] ?? '';
            $fr = !empty($_POST['fr']) ? intval($_POST['fr']) : null;
            $temperatura = !empty($_POST['temperatura']) ? floatval($_POST['temperatura']) : null;
            $fc = !empty($_POST['fc']) ? intval($_POST['fc']) : null;
            $taSistolica = !empty($_POST['ta_sistolica']) ? intval($_POST['ta_sistolica']) : null;
            $taDiastolica = !empty($_POST['ta_diastolica']) ? intval($_POST['ta_diastolica']) : null;
            $satO2 = !empty($_POST['sat_o2']) ? intval($_POST['sat_o2']) : null;
            $glucemia = !empty($_POST['glucemia']) ? intval($_POST['glucemia']) : null;
            
            // Validar hora
            if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Formato de hora inválido'
                ]);
                exit;
            }
            
            // Obtener fecha de hoja actual (fecha del turno hospitalario)
            $fechaHoja = date('Y-m-d');
            $horaActual = intval(date('H'));
            if ($horaActual < 8) {
                // Si es antes de las 8 AM, pertenece al día anterior
                $fechaHoja = date('Y-m-d', strtotime('-1 day'));
            }
            
            // Validar rangos de constantes
            $errores = [];
            if ($fr !== null && ($fr < 0 || $fr > 50)) $errores[] = 'FR debe estar entre 0-50';
            if ($temperatura !== null && ($temperatura < 33 || $temperatura > 42)) $errores[] = 'Temperatura debe estar entre 33-42°C';
            if ($fc !== null && ($fc < 0 || $fc > 250)) $errores[] = 'FC debe estar entre 0-250';
            if ($taSistolica !== null && ($taSistolica < 0 || $taSistolica > 250)) $errores[] = 'TA sistólica debe estar entre 0-250';
            if ($taDiastolica !== null && ($taDiastolica < 0 || $taDiastolica > 250)) $errores[] = 'TA diastólica debe estar entre 0-250';
            if ($satO2 !== null && ($satO2 < 0 || $satO2 > 100)) $errores[] = 'Saturación O2 debe estar entre 0-100%';
            if ($glucemia !== null && ($glucemia < 0 || $glucemia > 999)) $errores[] = 'Glucemia debe estar entre 0-999';
            
            if (!empty($errores)) {
                echo json_encode([
                    'success' => false,
                    'error' => implode(', ', $errores)
                ]);
                exit;
            }
            
            // Insertar o actualizar constantes
            $stmt = $pdo->prepare("
                INSERT INTO constantes_vitales 
                (numero_box, hora, fecha_hoja, fr, temperatura, fc, ta_sistolica, ta_diastolica, sat_o2, glucemia) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    fr = VALUES(fr),
                    temperatura = VALUES(temperatura),
                    fc = VALUES(fc),
                    ta_sistolica = VALUES(ta_sistolica),
                    ta_diastolica = VALUES(ta_diastolica),
                    sat_o2 = VALUES(sat_o2),
                    glucemia = VALUES(glucemia),
                    fecha_registro = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $numeroBox, $hora, $fechaHoja, $fr, $temperatura, $fc, 
                $taSistolica, $taDiastolica, $satO2, $glucemia
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Constantes vitales guardadas correctamente',
                'box' => $numeroBox,
                'hora' => $hora,
                'fecha_hoja' => $fechaHoja
            ]);
            break;
            
        case 'eliminar_constantes':
            $hora = $_POST['hora'] ?? '';
            
            // Validar hora
            if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Formato de hora inválido'
                ]);
                exit;
            }
            
            // Obtener fecha de hoja actual
            $fechaHoja = date('Y-m-d');
            $horaActual = intval(date('H'));
            if ($horaActual < 8) {
                $fechaHoja = date('Y-m-d', strtotime('-1 day'));
            }
            
            // Eliminar constantes
            $stmt = $pdo->prepare("
                DELETE FROM constantes_vitales 
                WHERE numero_box = ? AND hora = ? AND fecha_hoja = ?
            ");
            
            $stmt->execute([$numeroBox, $hora, $fechaHoja]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Constantes eliminadas correctamente',
                    'box' => $numeroBox,
                    'hora' => $hora
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'No se encontraron constantes para eliminar'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }

} catch (PDOException $e) {
    error_log("Error en gestionar_constantes.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos'
    ]);
    
} catch (Exception $e) {
    error_log("Error general en gestionar_constantes.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>