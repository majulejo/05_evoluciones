<?php
require_once("conexion.php");

header("Content-Type: application/json");
$ADMIN_CODE = "faroladmin2024";

// Verifica clave de admin
$headers = array_change_key_case(getallheaders(), CASE_LOWER); // convierte todo a minúsculas
if (!isset($headers['x-admin-key']) || $headers['x-admin-key'] !== 'faroladmin2024') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acceso denegado"]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === "list") {
    $sql = "SELECT id, usuario FROM usuarios ORDER BY id ASC";
    $result = $conn->query($sql);

    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }

    echo json_encode(["success" => true, "usuarios" => $usuarios]);
    exit;
}

if ($action === "create") {
    $input = json_decode(file_get_contents("php://input"), true);
    $usuario = trim($input["usuario"]);
    $clave = trim($input["clave"]);

    if (!$usuario || !$clave) {
        echo json_encode(["success" => false, "message" => "Faltan datos."]);
        exit;
    }

    $hash = password_hash($clave, PASSWORD_BCRYPT);

    try {
        $stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave) VALUES (?, ?)");
        $stmt->bind_param("ss", $usuario, $hash);
        $stmt->execute();
        echo json_encode(["success" => true, "message" => "Usuario registrado correctamente."]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error al registrar usuario."]);
    }
    exit;
}

if ($action === "delete") {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = intval($input["id"]);

    if (!$id) {
        echo json_encode(["success" => false, "message" => "ID no válido."]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Usuario eliminado correctamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "No se pudo eliminar."]);
    }
    exit;
}