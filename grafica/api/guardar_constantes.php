<?php
// ===== GUARDAR_CONSTANTES.PHP - API PARA GUARDAR CONSTANTES VITALES =====

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
    if (!isset($data['box']) || !isset($data['hora'])) {
        manejarError('Campos box y hora son requeridos', 400);
    }

    $box = (int)$data['box'];
    $hora = $data['hora'];
    $hoja = $data['hoja'] ?? 1;

    log_debug("Guardando constantes para box: $box, hora: $hora, hoja: $hoja");

    // Conectar a la base de datos
    $pdo = conectarBD();

    // Verificar que el paciente existe
    $stmt = $pdo->prepare("SELECT box FROM pacientes WHERE box = ?");
    $stmt->execute([$box]);
    
    if (!$stmt->fetch()) {
        manejarError("No existe paciente en box $box", 404);
    }

    // Preparar datos para insertar/actualizar
    $campos = [];
    $valores = [$box, $hora, $hoja];
    $placeholders = ['?', '?', '?'];

    // Mapear campos opcionales
    $camposOpcionales = [
        'fr' => 'fr',
        'temperatura' => 'temperatura', 
        'fc' => 'fc',
        'ta_sistolica' => 'ta_sistolica',
        'ta_diastolica' => 'ta_diastolica',
        'saturacion' => 'saturacion',
        'glucemia' => 'glucemia'
    ];

    foreach ($camposOpcionales as $key => $campo) {
        if (isset($data[$key]) && $data[$key] !== null && $data[$key] !== '') {
            $campos[] = $campo;
            $valores[] = $data[$key];
            $placeholders[] = '?';
        }
    }

    // Construir query de INSERT ... ON DUPLICATE KEY UPDATE
    $camposStr = implode(', ', array_merge(['box', 'hora', 'hoja'], $campos));
    $placeholdersStr = implode(', ', $placeholders);
    
    $updateParts = [];
    foreach ($campos as $campo) {
        $updateParts[] = "$campo = VALUES($campo)";
    }
    $updateStr = implode(', ', $updateParts);

    $sql = "INSERT INTO constantes_vitales ($camposStr) VALUES ($placeholdersStr)";
    if (!empty($updateParts)) {
        $sql .= " ON DUPLICATE KEY UPDATE $updateStr";
    }

    log_debug("SQL: $sql");
    log_debug("Valores: " . json_encode($valores));

    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);

    log_debug("Constantes guardadas exitosamente");

    // Respuesta exitosa
    responderJSON([
        'success' => true,
        'message' => 'Constantes guardadas correctamente',
        'data' => [
            'box' => $box,
            'hora' => $hora,
            'hoja' => $hoja,
            'campos_guardados' => $campos
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