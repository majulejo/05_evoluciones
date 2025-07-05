<?php
header("Content-Type: application/json");

// Incluir configuración si es necesario
require_once '../config.php'; // o donde tengas tu conexión

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['box'], $data['hora'], $data['campo'], $data['valor'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$box = intval($data['box']);
$hora = htmlspecialchars($data['hora']);
$campo = htmlspecialchars($data['campo']);
$valor = htmlspecialchars($data['valor']);

try {
    // Conexión a la base de datos (ajusta esto según tus datos)
    $pdo = new PDO("mysql:host=localhost;dbname=tu_basedatos;charset=utf8", "tu_usuario", "tu_contraseña");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Guardar en BD (ajustar nombre de tabla/columnas según tu estructura)
    $stmt = $pdo->prepare("
        INSERT INTO oxigenacion_datos (box, hora, campo, valor)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE valor = ?
    ");
    $stmt->execute([$box, $hora, $campo, $valor, $valor]);

    echo json_encode([
        'success' => true,
        'message' => 'Dato guardado correctamente',
        'data' => $data
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>