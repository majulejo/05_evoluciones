<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php'; // conexión MySQLi

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 0);

try {
    if (empty($_POST['usuario']) || empty($_POST['clave'])) {
        throw new Exception("Usuario y clave requeridos");
    }

    $usuario = $_POST['usuario'];
    $clave   = $_POST['clave'];

    $stmt = $db->prepare("SELECT id, clave FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // ✅ Comprobamos el hash usando password_verify
        if (password_verify($clave, $row['clave'])) {
            $_SESSION['user_id'] = $row['id'];
            echo json_encode([
                'success' => true,
                'user_id' => $row['id']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Credenciales incorrectas.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado.'
        ]);
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
