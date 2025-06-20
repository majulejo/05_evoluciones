<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

require_once '../db.php'; // Ajusta la ruta si es necesario

$data = json_decode(file_get_contents('php://input'), true);

$usuario_id = $_SESSION['user_id'];
$peso = $data['peso-box'] ?? null;
$horas = $data['horas-desde-ingreso-box'] ?? null;
$diuresis = $data['perdida-orina-box'] ?? null;
$midazolam = $data['ingreso-midazolam-box'] ?? null;

try {
    $stmt = $db->prepare("
        INSERT INTO datos_balance (
            usuario_id, peso, horas_ingreso, diuresis, midazolam
        ) VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            peso = VALUES(peso),
            horas_ingreso = VALUES(horas_ingreso),
            diuresis = VALUES(diuresis),
            midazolam = VALUES(midazolam)
    ");

    $stmt->bind_param("idddi", $usuario_id, $peso, $horas, $diuresis, $midazolam);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
}
?>