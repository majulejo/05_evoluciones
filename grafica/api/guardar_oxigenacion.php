<?php
// ===== GUARDAR_OXIGENACION.PHP - API PARA GUARDAR DATOS DE OXIGENACIÓN =====

require_once 'config.php';

try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido', 405);
    }

    // Obtener datos JSON del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        manejarError('Datos JSON inválidos', 400);
    }

    // Validar campos requeridos
    if (!isset($data['box']) || !isset($data['hora']) || !isset($data['campo']) || !isset($data['valor'])) {
        manejarError('Campos box, hora, campo y valor son requeridos', 400);
    }

    $box = (int)$data['box'];
    $hora = $data['hora'];
    $campo = $data['campo'];
    $valor = $data['valor'];
    $hoja = $data['hoja'] ?? 1;

    log_debug("Guardando oxigenación: box=$box, hora=$hora, campo=$campo, valor=$valor, hoja=$hoja");

    // Conectar a la base de datos
    $pdo = conectarBD();

    // Verificar que el paciente existe
    $stmt = $pdo->prepare("SELECT box FROM pacientes WHERE box = ?");
    $stmt->execute([$box]);
    
    if (!$stmt->fetch()) {
        manejarError("No existe paciente en box $box", 404);
    }

    // Mapear campos permitidos
    $camposPermitidos = [
        'pNeumo' => 'p_neumo',
        'oxigenacion' => 'oxigenacion',
        'evaRass' => 'eva_escid',
        'insulina' => 'insulina',
        'saturacion' => 'saturacion',
        'glucemia' => 'glucemia'
    ];

    if (!isset($camposPermitidos[$campo])) {
        manejarError("Campo '$campo' no válido", 400);
    }

    $campoBaseDatos = $camposPermitidos[$campo];

    // Verificar si ya existe un registro para esta hora
    $stmt = $pdo->prepare("SELECT id FROM datos_oxigenacion WHERE box = ? AND hora = ? AND hoja = ?");
    $stmt->execute([$box, $hora, $hoja]);
    $registroExistente = $stmt->fetch();

    if ($registroExistente) {
        // Actualizar registro existente
        $sql = "UPDATE datos_oxigenacion SET $campoBaseDatos = ? WHERE box = ? AND hora = ? AND hoja = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$valor, $box, $hora, $hoja]);
        log_debug("Registro actualizado");
    } else {
        // Insertar nuevo registro
        $sql = "INSERT INTO datos_oxigenacion (box, hora, hoja, $campoBaseDatos) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$box, $hora, $hoja, $valor]);
        log_debug("Nuevo registro insertado");
    }

    log_debug("Dato de oxigenación guardado exitosamente");

    // Respuesta exitosa
    responderJSON([
        'success' => true,
        'message' => 'Dato de oxigenación guardado correctamente',
        'data' => [
            'box' => $box,
            'hora' => $hora,
            'hoja' => $hoja,
            'campo' => $campo,
            'valor' => $valor,
            'campo_bd' => $campoBaseDatos
        ]
    ]);

} catch (PDOException $e) {
    log_debug("Error PDO: " . $e->getMessage());
    manejarError('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    log_debug("Error general: " . $e->getMessage());
    manejarError('Error interno del servidor: ' . $e->getMessage(), 500);
}
?>