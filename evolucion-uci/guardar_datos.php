//guardar_datos.php
<?php
header('Content-Type: application/json');
date_default_timezone_set('Europe/Madrid');

// --- Configuración de la base de datos ---
// Asumo que tienes un archivo de conexión.php o db.php.
// Si no, descomenta y configura las siguientes líneas.
// Ejemplo: include 'conexion.php';
// Ejemplo: include 'db.php';

$host = 'localhost'; // O la IP/dominio de tu servidor de DB
$db   = 'u724879249_evolucion_uci'; // Reemplaza con el nombre real de tu DB
$user = 'u724879249_jamarquez06'; // Reemplaza con tu usuario de DB
$pass = 'Farolill01.'; // Reemplaza con tu contraseña de DB
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
// --- FIN Configuración de la base de datos ---

// --- ID de usuario (IMPORTANTE) ---
// Aquí necesitas obtener el usuario_id de tu sistema de sesión de forma segura.
// Por ejemplo, si usas sesiones PHP:
session_start(); // Asegúrate de iniciar la sesión
$usuario_id = $_SESSION['user_id'] ?? 1; // **¡AJUSTA ESTO!** Usa el ID real del usuario logueado.
                                        // Si no hay sesión o user_id, 1 es un valor por defecto.

// Manejar la solicitud POST (guardar o borrar datos)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $request_data = json_decode($input, true);

    $box_number = $request_data['box'] ?? null; // Recibimos 'box' de JS y lo mapeamos a 'box_number' en DB
    $clear_data = $request_data['clear_data'] ?? false; // Flag para borrar datos

    if ($box_number === null) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Número de box no especificado.']);
        exit();
    }

    if ($clear_data) {
        // Borrar el registro del box de la base de datos para este usuario
        $stmt = $pdo->prepare("DELETE FROM datos_balance WHERE usuario_id = ? AND box_number = ?");
        if ($stmt->execute([$usuario_id, $box_number])) {
            echo json_encode(['status' => 'success', 'message' => 'Datos borrados para el Box ' . $box_number]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar datos: ' . implode(" ", $stmt->errorInfo())]);
        }
    } else {
        // Preparar los datos para la inserción/actualización, usando los nombres de las columnas de la DB
        $data_to_save = [
            'peso' => $request_data['peso'] ?? 0,
            'horas_desde_ingreso' => $request_data['horas_desde_ingreso'] ?? 0,
            'diuresis' => $request_data['diuresis'] ?? 0,
            'vomitos_sudor' => $request_data['vomitos_sudor'] ?? 0,
            'fiebre37_horas' => $request_data['fiebre37_horas'] ?? 0,
            'fiebre38_horas' => $request_data['fiebre38_horas'] ?? 0,
            'fiebre39_horas' => $request_data['fiebre39_horas'] ?? 0,
            'rpm25_horas' => $request_data['rpm25_horas'] ?? 0,
            'rpm35_horas' => $request_data['rpm35_horas'] ?? 0,
            'sng' => $request_data['sng'] ?? 0,
            'hdfvvc' => $request_data['hdfvvc'] ?? 0,
            'drenajes' => $request_data['drenajes'] ?? 0,
            'perdidas_insensibles' => $request_data['perdidas_insensibles'] ?? 0,
            'calculo_vomitos_sudor' => $request_data['calculo_vomitos_sudor'] ?? 0,
            'total_perdidas' => $request_data['total_perdidas'] ?? 0,
            'midazolam' => $request_data['midazolam'] ?? 0,
            'fentanest' => $request_data['fentanest'] ?? 0,
            'propofol' => $request_data['propofol'] ?? 0,
            'remifentanilo' => $request_data['remifentanilo'] ?? 0,
            'dexdor' => $request_data['dexdor'] ?? 0,
            'noradrenalina' => $request_data['noradrenalina'] ?? 0,
            'insulina' => $request_data['insulina'] ?? 0,
            'suero_terapia1' => $request_data['suero_terapia1'] ?? 0,
            'suero_terapia2' => $request_data['suero_terapia2'] ?? 0,
            'suero_terapia3' => $request_data['suero_terapia3'] ?? 0,
            'medicacion' => $request_data['medicacion'] ?? 0,
            'sangre_plasma' => $request_data['sangre_plasma'] ?? 0,
            'oral' => $request_data['oral'] ?? 0,
            'enteral' => $request_data['enteral'] ?? 0,
            'parenteral' => $request_data['parenteral'] ?? 0,
            'total_ingresos' => $request_data['total_ingresos'] ?? 0,
            'balance_total' => $request_data['balance_total'] ?? 0
        ];

        // Columnas para la sentencia INSERT
        $insert_columns = ['usuario_id', 'box_number'];
        $insert_placeholders = [':usuario_id', ':box_number'];
        foreach (array_keys($data_to_save) as $col) {
            $insert_columns[] = $col;
            $insert_placeholders[] = ":$col";
        }
        $insert_columns_str = implode(', ', $insert_columns);
        $insert_placeholders_str = implode(', ', $insert_placeholders);

        // Columnas para la sentencia ON DUPLICATE KEY UPDATE
        $update_parts = [];
        foreach (array_keys($data_to_save) as $col) {
            $update_parts[] = "$col = VALUES($col)";
        }
        $update_clause = implode(', ', $update_parts);

        // Sentencia SQL final
        $sql = "INSERT INTO datos_balance ($insert_columns_str, fecha)
                VALUES ($insert_placeholders_str, NOW())
                ON DUPLICATE KEY UPDATE
                    $update_clause,
                    fecha = NOW()"; // Siempre actualizar la fecha en cada modificación

        $stmt = $pdo->prepare($sql);

        // Mapear los datos a los placeholders
        $bind_params = [
            ':usuario_id' => $usuario_id,
            ':box_number' => $box_number // Aquí usamos ':box_number' porque así está en la tabla
        ];
        foreach ($data_to_save as $key => $value) {
            $bind_params[":$key"] = $value;
        }

        if ($stmt->execute($bind_params)) {
            echo json_encode(['status' => 'success', 'message' => 'Datos guardados para el Box ' . $box_number]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar datos: ' . implode(" ", $stmt->errorInfo())]);
        }
    }
}
// Manejar la solicitud GET (cargar datos de un box específico)
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $box_number = $_GET['box'] ?? null;

    if ($box_number === null) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Número de box no especificado para la carga.']);
        exit();
    }

    // Seleccionar TODAS las columnas que se están guardando
    $select_columns = [
        'peso', 'horas_desde_ingreso', 'diuresis', 'vomitos_sudor',
        'fiebre37_horas', 'fiebre38_horas', 'fiebre39_horas',
        'rpm25_horas', 'rpm35_horas', 'sng', 'hdfvvc', 'drenajes',
        'perdidas_insensibles', 'calculo_vomitos_sudor', 'total_perdidas',
        'midazolam', 'fentanest', 'propofol', 'remifentanilo', 'dexdor',
        'noradrenalina', 'insulina', 'suero_terapia1', 'suero_terapia2', 'suero_terapia3',
        'medicacion', 'sangre_plasma', 'oral', 'enteral', 'parenteral',
        'total_ingresos', 'balance_total'
    ];

    $sql = "SELECT " . implode(', ', $select_columns) . " FROM datos_balance WHERE usuario_id = ? AND box_number = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, $box_number]);
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode([]); // Devolver un objeto JSON vacío si no hay datos para ese box/usuario
    }
}
// Manejar otros métodos no permitidos
else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}

?>