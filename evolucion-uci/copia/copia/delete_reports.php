<?php
header('Content-Type: application/json; charset=utf-8');


// Agregar logs para depuración
error_log("Entrando a delete_reports.php");
error_log(print_r($_POST, true));


// Conexión a la base de datos
$mysqli = new mysqli("localhost", "u724879249_jamarquez06", "Farolill01.", "u724879249_evolucion_uci");

if ($mysqli->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
    exit;
}

// Obtener datos enviados por POST
$data = json_decode(file_get_contents("php://input"), true);

$user_id = isset($data['user_id']) ? $data['user_id'] : null;
$id = isset($data['id']) ? $data['id'] : null;

if (!$user_id || !$id) {
    echo json_encode(["success" => false, "message" => "Faltan parámetros necesarios"]);
    exit;
}

// Consulta SQL para eliminar el informe
$sql = "DELETE FROM informes WHERE user_id = ? AND id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ss", $user_id, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Informe eliminado correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al eliminar el informe"]);
}

$stmt->close();
$mysqli->close();
?>