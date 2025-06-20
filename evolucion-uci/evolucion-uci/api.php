<?php
header("Content-Type: application/json");
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

// 5) Funciones de ejemplo (deberás rellenarlas con tu lógica real)

function saveData($pdo, $userId, $data) {
    // Aquí va tu lógica de guardado
    echo json_encode(["success" => true, "message" => "Datos guardados"]);
}

function loadData($pdo, $userId, $data) {
    // Aquí va tu lógica de carga
    echo json_encode(["success" => true, "data" => []]);
}

function deleteAll($pdo, $userId, $data) {
    // Aquí va tu lógica de eliminación total
    echo json_encode(["success" => true, "message" => "Todos los datos eliminados"]);
}

function deleteIngresos($pdo, $userId, $data) {
    // Aquí va tu lógica de eliminar ingresos
    echo json_encode(["success" => true, "message" => "Ingresos eliminados"]);
}

function deletePerdidas($pdo, $userId, $data) {
    // Aquí va tu lógica de eliminar pérdidas
    echo json_encode(["success" => true, "message" => "Pérdidas eliminadas"]);
}