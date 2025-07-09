<?php
// ===== OBTENER_MAX_HOJA.PHP - API PARA OBTENER MÁXIMA HOJA CON DATOS =====

require_once 'config.php';

try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        manejarError('Método no permitido', 405);
    }

    // Obtener parámetros
    $box = $_GET['box'] ?? null;

    if (!$box || !is_numeric($box)) {
        manejarError('Parámetro box requerido y debe ser numérico', 400);
    }

    $box = (int)$box;
    log_debug("Obteniendo máxima hoja para box: $box");

    // Conectar a la base de datos
    $pdo = conectarBD();

    // Verificar que el paciente existe
    $stmt = $pdo->prepare("SELECT box FROM pacientes WHERE box = ?");
    $stmt->execute([$box]);
    
    if (!$stmt->fetch()) {
        manejarError("No existe paciente en box $box", 404);
    }

    // Obtener máxima hoja de constantes vitales
    $stmt = $pdo->prepare("SELECT MAX(hoja) as max_hoja FROM constantes_vitales WHERE box = ?");
    $stmt->execute([$box]);
    $maxHojaConstantes = $stmt->fetchColumn() ?: 0;

    // Obtener máxima hoja de datos de oxigenación
    $stmt = $pdo->prepare("SELECT MAX(hoja) as max_hoja FROM datos_oxigenacion WHERE box = ?");
    $stmt->execute([$box]);
    $maxHojaOxigenacion = $stmt->fetchColumn() ?: 0;

    // La máxima hoja es el mayor entre ambas tablas
    $maxHoja = max($maxHojaConstantes, $maxHojaOxigenacion, 1);

    log_debug("Máxima hoja encontrada: $maxHoja (constantes: $maxHojaConstantes, oxigenación: $maxHojaOxigenacion)");

    // Respuesta exitosa
    responderJSON([
        'success' => true,
        'message' => "Máxima hoja para box $box: $maxHoja",
        'maxHoja' => $maxHoja,
        'detalles' => [
            'max_hoja_constantes' => $maxHojaConstantes,
            'max_hoja_oxigenacion' => $maxHojaOxigenacion,
            'box' => $box
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