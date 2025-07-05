

<?php
// archivo: api/dar_alta_paciente.php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $box = isset($input['box']) ? intval($input['box']) : 0;
    $paciente = isset($input['paciente']) ? $input['paciente'] : null;
    $fecha_alta = isset($input['fecha_alta']) ? $input['fecha_alta'] : date('Y-m-d H:i:s');

    if ($box <= 0 || !$paciente) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $pdo = obtenerConexionBD();
    $pdo->beginTransaction();

    try {
        // 1. Actualizar el paciente como inactivo y establecer fecha de alta
        $stmt = $pdo->prepare("
            UPDATE pacientes 
            SET activo = 0, fecha_alta = ? 
            WHERE box = ? AND activo = 1
        ");
        $stmt->execute([$fecha_alta, $box]);

        // 2. Opcional: Mover datos a tabla de historial (si existe)
        $stmt = $pdo->prepare("
            INSERT INTO pacientes_historial 
            (box, nombre_completo, edad, peso, numero_historia, fecha_ingreso, fecha_alta, motivo_alta)
            SELECT box, nombre_completo, edad, peso, numero_historia, fecha_ingreso, ?, 'Alta médica'
            FROM pacientes 
            WHERE box = ? AND activo = 0 AND fecha_alta = ?
        ");
        $stmt->execute([$fecha_alta, $box, $fecha_alta]);

        // 3. Limpiar datos de monitoreo activo (opcional)
        $stmt = $pdo->prepare("DELETE FROM constantes_vitales WHERE box = ?");
        $stmt->execute([$box]);

        $stmt = $pdo->prepare("DELETE FROM datos_oxigenacion WHERE box = ?");
        $stmt->execute([$box]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Alta procesada correctamente',
            'box_liberado' => $box,
            'fecha_alta' => $fecha_alta
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error en dar_alta_paciente.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar alta: ' . $e->getMessage()
    ]);
}
?>

