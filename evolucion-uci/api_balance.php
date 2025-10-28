<?php
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// !!! TEMPORALMENTE: HABILITAR ERRORES PARA DEPURACIÓN.            !!!
// !!! ¡QUITA ESTO EN PRODUCCIÓN PARA SEGURIDAD!                   !!!
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
ini_set('display_errors', 1);
error_reporting(E_ALL);
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

date_default_timezone_set('Europe/Madrid');
header('Content-Type: application/json; charset=utf-8');
session_start(); // Inicia la sesión PHP

// --- Configuración de la base de datos ---
$host = 'localhost';
$db   = 'u724879249_evolucion_uci';
$user = 'u724879249_jamarquez06';
$pass = 'Farolill01.';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit();
}

// --- ID de usuario ---
// Si vienes de un sistema de login, $_SESSION['user_id'] debe estar seteado.
// Para propósitos de prueba, si no tienes login, puedes usar un ID fijo:
$usuario_id = $_SESSION['user_id'] ?? 1; // !!!!!!! CAMBIA ESTO PARA PRODUCCIÓN O ASEGÚRATE QUE $_SESSION['user_id'] SE SETEE !!!!!!!

if ($usuario_id === null) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $request_data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'JSON de entrada inválido: ' . json_last_error_msg()]);
        exit();
    }

    $box_number = $request_data['box_number'] ?? null;
    $clear_data = $request_data['clear_data'] ?? false;

    if ($box_number === null) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Número de box no especificado.']);
        exit();
    }

    if ($clear_data) {
        $stmt = $pdo->prepare("DELETE FROM datos_balance WHERE usuario_id = ? AND box_number = ?");
        if ($stmt->execute([$usuario_id, (int)$box_number])) {
            echo json_encode(['status' => 'success', 'message' => 'Datos borrados para el Box ' . $box_number]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar datos: ' . implode(" ", $stmt->errorInfo())]);
        }
    } else {
        $db_columns_map = [
            'peso'                   => 'peso',
            'horasDesdeIngreso'      => 'horas_desde_ingreso',
            'perdidaOrina'           => 'perdida_orina',
            'perdidaVomitos'         => 'vomitos_sudor',
            'fiebre37Horas'          => 'fiebre37_horas',
            'fiebre37Calculo'        => 'fiebre37_calculo',
            'fiebre38Horas'          => 'fiebre38_horas',
            'fiebre38Calculo'        => 'fiebre38_calculo',
            'fiebre39Horas'          => 'fiebre39_horas',
            'fiebre39Calculo'        => 'fiebre39_calculo',
            'rpm25Horas'             => 'rpm25_horas',
            'rpm25Calculo'           => 'rpm25_calculo',
            'rpm35Horas'             => 'rpm35_horas',
            'rpm35Calculo'           => 'rpm35_calculo',
            'perdidaSng'             => 'sng',
            'perdidaHdfvvc'          => 'hdfvvc',
            'perdidaDrenajes'        => 'drenajes',
            'perdidasInsensibles'    => 'perdidas_insensibles',
            'perdidaFueraFluidos'    => 'calculo_vomitos_sudor',
            'totalPerdidas'          => 'total_perdidas',
            'ingresoMidazolam'       => 'midazolam',
            'ingresoFentanest'       => 'fentanest',
            'ingresoPropofol'        => 'propofol',
            'ingresoRemifentanilo'   => 'remifentanilo',
            'ingresoDexdor'          => 'dexdor',
            'ingresoNoradrenalina'   => 'noradrenalina',
            'ingresoInsulina'        => 'insulina',
            'ingresoSueroterapia1'   => 'suero_terapia1',
            'ingresoSueroterapia2'   => 'suero_terapia2',
            'ingresoSueroterapia3'   => 'suero_terapia3',
            'ingresoMedicacion'      => 'medicacion',
            'ingresoSangrePlasma'    => 'sangre_plasma',
            'ingresoAguaEndogena'    => 'ingreso_agua_endogena',
            'ingresoOral'            => 'oral',
            'enteral'                => 'enteral', // Asegúrate que tu DB tiene 'enteral' y 'parenteral'
            'parenteral'             => 'parenteral',
            'totalIngresos'          => 'total_ingresos',
            'balanceTotal'           => 'balance_total'
        ];

        $data_to_save = [];
        $columns_for_insert = [];
        $placeholders_for_insert = [];
        $update_parts = [];

        foreach ($db_columns_map as $js_key => $db_column) {
            $value = $request_data[$js_key] ?? 0;
            if ($value === "") { // Convertir strings vacíos a 0
                $value = 0;
            }
            $data_to_save[$db_column] = $value;
            $columns_for_insert[] = $db_column;
            $placeholders_for_insert[] = ":$db_column";
            $update_parts[] = "$db_column = VALUES($db_column)";
        }

        $insert_columns_str = implode(', ', $columns_for_insert);
        $insert_placeholders_str = implode(', ', $placeholders_for_insert);
        $update_clause = implode(', ', $update_parts);

        $sql = "INSERT INTO datos_balance (usuario_id, box_number, $insert_columns_str, fecha)
                VALUES (:usuario_id, :box_number, $insert_placeholders_str, NOW())
                ON DUPLICATE KEY UPDATE
                fecha = NOW(),
                $update_clause";

        $stmt = $pdo->prepare($sql);

        $bind_params = [
            ':usuario_id' => $usuario_id,
            ':box_number' => (int)$box_number,
        ];
        foreach ($data_to_save as $db_column => $value) {
            $bind_params[":$db_column"] = $value;
        }

        if ($stmt->execute($bind_params)) {
            echo json_encode(['status' => 'success', 'message' => 'Datos guardados para el Box ' . $box_number]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar datos: ' . implode(" ", $stmt->errorInfo())]);
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $box_number = $_GET['box_number'] ?? null;

    if ($box_number === null) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Número de box no especificado para la carga.']);
        exit();
    }

    $sql_select_columns = [
        'peso', 'horas_desde_ingreso AS horasDesdeIngreso', 'perdida_orina AS perdidaOrina',
        'vomitos_sudor AS perdidaVomitos', 'fiebre37_horas AS fiebre37Horas', 'fiebre37_calculo AS fiebre37Calculo',
        'fiebre38_horas AS fiebre38Horas', 'fiebre38_calculo AS fiebre38Calculo', 'fiebre39_horas AS fiebre39Horas',
        'fiebre39_calculo AS fiebre39Calculo', 'rpm25_horas AS rpm25Horas', 'rpm25_calculo AS rpm25Calculo',
        'rpm35_horas AS rpm35Horas', 'rpm35_calculo AS rpm35Calculo', 'sng AS perdidaSng',
        'hdfvvc AS perdidaHdfvvc', 'drenajes AS perdidaDrenajes', 'perdidas_insensibles AS perdidasInsensibles',
        'calculo_vomitos_sudor AS perdidaFueraFluidos', 'total_perdidas AS totalPerdidas',
        'midazolam AS ingresoMidazolam', 'fentanest AS ingresoFentanest', 'propofol AS ingresoPropofol',
        'remifentanilo AS ingresoRemifentanilo', 'dexdor AS ingresoDexdor', 'noradrenalina AS ingresoNoradrenalina',
        'insulina AS ingresoInsulina', 'suero_terapia1 AS ingresoSueroterapia1', 'suero_terapia2 AS ingresoSueroterapia2',
        'suero_terapia3 AS ingresoSueroterapia3', 'medicacion AS ingresoMedicacion', 'sangre_plasma AS ingresoSangrePlasma',
        'ingreso_agua_endogena AS ingresoAguaEndogena', 'oral AS ingresoOral', 'enteral AS enteral', 'parenteral AS parenteral', // Asegúrate que tu DB tiene 'enteral' y 'parenteral'
        'total_ingresos AS totalIngresos', 'balance_total AS balanceTotal'
    ];

    $sql = "SELECT " . implode(', ', $sql_select_columns) . " FROM datos_balance WHERE usuario_id = ? AND box_number = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, (int)$box_number]);
    $result = $stmt->fetch();

    if ($result) {
        // Asegúrate de que estos campos existan y se pasen al JS si no están en DB
        $result['resumenTotalIngresosSummary'] = $result['totalIngresos'];
        $result['totalPerdidasSummary'] = $result['totalPerdidas'];
        echo json_encode(['status' => 'success', 'data' => $result]);
    } else {
        echo json_encode(['status' => 'success', 'data' => []]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>