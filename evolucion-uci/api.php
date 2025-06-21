<?php
header("Content-Type: application/json");
header('Cache-Control: no-cache, must-revalidate');
session_start();

// 1) Configuración de la base de datos
$host = "localhost";
$db = "u724879249_evolucion_uci";
$user = "u724879249_jamarquez06";
$pass = "Farolill01.";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión a la base de datos: " . $e->getMessage()
    ]);
    exit;
}

// 2) Leer payload
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data) || !isset($data['action'])) {
    echo json_encode([
        "success" => false,
        "message" => "Petición mal formada o falta 'action'."
    ]);
    exit;
}

// 3) Obtener userId desde sesión
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no autenticado."
    ]);
    exit;
}

// 4) Ejecutar acción
switch ($data['action']) {
    case 'save':
        saveData($pdo, $userId, $data);
        break;

    case 'load':
        loadData($pdo, $userId, $data);
        break;

    case 'deleteAll':
        deleteAll($pdo, $userId, $data);
        break;

    case 'deleteIngresos':
        deleteIngresos($pdo, $userId, $data);
        break;

    case 'deletePerdidas':
        deletePerdidas($pdo, $userId, $data);
        break;

    default:
        echo json_encode([
            "success" => false,
            "message" => "Acción no reconocida."
        ]);
        break;
}

// 5) Funciones implementadas

function saveData($pdo, $userId, $data) {
    try {
        $boxNumber = $data['boxNumber'] ?? null;
        $campos = $data['data'] ?? [];

        if (!$boxNumber) {
            echo json_encode([
                "success" => false,
                "message" => "Falta boxNumber"
            ]);
            return;
        }

        // Verificar si ya existe un registro para este usuario y box
        $checkStmt = $pdo->prepare("SELECT id FROM datos_balance WHERE usuario_id = ? AND box_number = ?");
        $checkStmt->execute([$userId, $boxNumber]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            // Actualizar registro existente
            $updateFields = [];
            $updateValues = [];
            
            foreach ($campos as $field => $value) {
                if (isValidField($field)) {
                    $dbField = convertFieldName($field);
                    if ($dbField) {
                        $updateFields[] = "`$dbField` = ?";
                        $updateValues[] = ($value === '' || $value === null) ? null : $value;
                    }
                }
            }
            
            if (!empty($updateFields)) {
                $updateValues[] = $userId;
                $updateValues[] = $boxNumber;
                
                $sql = "UPDATE datos_balance SET " . implode(', ', $updateFields) . " WHERE usuario_id = ? AND box_number = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateValues);
                
                echo json_encode([
                    "success" => true,
                    "message" => "Datos actualizados correctamente"
                ]);
            } else {
                echo json_encode([
                    "success" => true,
                    "message" => "No hay campos válidos para actualizar"
                ]);
            }
        } else {
            // Crear nuevo registro
            $fields = ['usuario_id', 'box_number'];
            $placeholders = ['?', '?'];
            $values = [$userId, $boxNumber];
            
            foreach ($campos as $field => $value) {
                if (isValidField($field)) {
                    $dbField = convertFieldName($field);
                    if ($dbField) {
                        $fields[] = "`$dbField`";
                        $placeholders[] = '?';
                        $values[] = ($value === '' || $value === null) ? null : $value;
                    }
                }
            }
            
            $sql = "INSERT INTO datos_balance (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            echo json_encode([
                "success" => true,
                "message" => "Registro creado correctamente"
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Error en saveData: " . $e->getMessage());
        echo json_encode([
            "success" => false,
            "message" => "Error al guardar datos: " . $e->getMessage()
        ]);
    }
}

function loadData($pdo, $userId, $data) {
    try {
        $boxNumber = $data['boxNumber'] ?? null;

        if (!$boxNumber) {
            echo json_encode([
                "success" => false,
                "message" => "Falta boxNumber"
            ]);
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM datos_balance WHERE usuario_id = ? AND box_number = ?");
        $stmt->execute([$userId, $boxNumber]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Convertir nombres de campos de BD a nombres de input
            $responseData = [];
            foreach ($result as $dbField => $value) {
                $inputField = convertDbFieldToInputField($dbField);
                if ($inputField) {
                    $responseData[$inputField] = $value;
                }
            }
            echo json_encode($responseData);
        } else {
            // No hay datos para este box
            echo json_encode([]);
        }
    } catch (PDOException $e) {
        error_log("Error en loadData: " . $e->getMessage());
        echo json_encode([
            "success" => false,
            "message" => "Error al cargar datos: " . $e->getMessage()
        ]);
    }
}

function deleteAll($pdo, $userId, $data) {
    try {
        $boxNumber = $data['boxNumber'] ?? null;

        if (!$boxNumber) {
            echo json_encode([
                "success" => false,
                "message" => "Falta boxNumber"
            ]);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM datos_balance WHERE usuario_id = ? AND box_number = ?");
        $stmt->execute([$userId, $boxNumber]);
        
        echo json_encode([
            "success" => true,
            "message" => "Todos los datos del Box $boxNumber han sido eliminados"
        ]);
    } catch (PDOException $e) {
        error_log("Error en deleteAll: " . $e->getMessage());
        echo json_encode([
            "success" => false,
            "message" => "Error al eliminar datos: " . $e->getMessage()
        ]);
    }
}

function deleteIngresos($pdo, $userId, $data) {
    try {
        $boxNumber = $data['boxNumber'] ?? null;

        if (!$boxNumber) {
            echo json_encode([
                "success" => false,
                "message" => "Falta boxNumber"
            ]);
            return;
        }

        // Campos de ingresos a poner en NULL
        $ingresoFields = [
            'ingreso_midazolam_box',
            'ingreso_fentanest_box',
            'ingreso_propofol_box',
            'ingreso_remifentanilo_box',
            'ingreso_dexdor_box',
            'ingreso_noradrenalina_box',
            'ingreso_insulina_box',
            'ingreso_sueroterapia1_box',
            'ingreso_sueroterapia2_box',
            'ingreso_sueroterapia3_box',
            'ingreso_medicacion_box',
            'ingreso_sangreplasma_box',
            'ingreso_oral_box',
            'ingreso_enteral_box',
            'ingreso_parenteral_box'
        ];
        
        $setClause = [];
        foreach ($ingresoFields as $field) {
            $setClause[] = "`$field` = NULL";
        }
        
        $sql = "UPDATE datos_balance SET " . implode(', ', $setClause) . " WHERE usuario_id = ? AND box_number = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $boxNumber]);
        
        echo json_encode([
            "success" => true,
            "message" => "Datos de ingresos del Box $boxNumber eliminados"
        ]);
    } catch (PDOException $e) {
        error_log("Error en deleteIngresos: " . $e->getMessage());
        echo json_encode([
            "success" => false,
            "message" => "Error al eliminar ingresos: " . $e->getMessage()
        ]);
    }
}

function deletePerdidas($pdo, $userId, $data) {
    try {
        $boxNumber = $data['boxNumber'] ?? null;

        if (!$boxNumber) {
            echo json_encode([
                "success" => false,
                "message" => "Falta boxNumber"
            ]);
            return;
        }

        // Campos de pérdidas a poner en NULL
        $perdidaFields = [
            'perdida_orina_box',
            'perdida_vomitos_box',
            'fiebre37_horas_box',
            'fiebre38_horas_box',
            'fiebre39_horas_box',
            'rpm25_horas_box',
            'rpm35_horas_box',
            'perdida_sng_box',
            'perdida_hdfvvc_box',
            'perdida_drenajes_box'
        ];
        
        $setClause = [];
        foreach ($perdidaFields as $field) {
            $setClause[] = "`$field` = NULL";
        }
        
        $sql = "UPDATE datos_balance SET " . implode(', ', $setClause) . " WHERE usuario_id = ? AND box_number = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $boxNumber]);
        
        echo json_encode([
            "success" => true,
            "message" => "Datos de pérdidas del Box $boxNumber eliminados"
        ]);
    } catch (PDOException $e) {
        error_log("Error en deletePerdidas: " . $e->getMessage());
        echo json_encode([
            "success" => false,
            "message" => "Error al eliminar pérdidas: " . $e->getMessage()
        ]);
    }
}

// 6) Funciones auxiliares

function isValidField($field) {
    $validFields = [
        'peso_box',
        'horas_desde_ingreso_box',
        'perdida_orina_box',
        'perdida_vomitos_box',
        'fiebre37_horas_box',
        'fiebre38_horas_box',
        'fiebre39_horas_box',
        'rpm25_horas_box',
        'rpm35_horas_box',
        'perdida_sng_box',
        'perdida_hdfvvc_box',
        'perdida_drenajes_box',
        'ingreso_midazolam_box',
        'ingreso_fentanest_box',
        'ingreso_propofol_box',
        'ingreso_remifentanilo_box',
        'ingreso_dexdor_box',
        'ingreso_noradrenalina_box',
        'ingreso_insulina_box',
        'ingreso_sueroterapia1_box',
        'ingreso_sueroterapia2_box',
        'ingreso_sueroterapia3_box',
        'ingreso_medicacion_box',
        'ingreso_sangreplasma_box',
        'ingreso_oral_box',
        'ingreso_enteral_box',
        'ingreso_parenteral_box'
    ];
    
    return in_array($field, $validFields);
}

function convertFieldName($inputField) {
    // Como los nombres coinciden con los de la BD, devolvemos el mismo nombre
    return isValidField($inputField) ? $inputField : null;
}

function convertDbFieldToInputField($dbField) {
    // Como los nombres coinciden, devolvemos el mismo nombre
    // Solo excluimos campos que no son inputs editables
    $excludeFields = ['id', 'usuario_id', 'box_number', 'fecha'];
    
    return in_array($dbField, $excludeFields) ? null : $dbField;
}
?>