<?php
// api/obtener_paciente.php - VERSIÓN CORREGIDA PARA TU ESTRUCTURA REAL

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ INCLUIR TU CONFIG.PHP
require_once '../config.php';

try {
    // ✅ OBTENER Y VALIDAR PARÁMETRO BOX
    $box = $_GET['box'] ?? null;
    
    if (!$box || !is_numeric($box)) {
        throw new Exception("Box inválido: " . $box);
    }
    
    $box = (int)$box;
    
    // ✅ USAR TU FUNCIÓN DE CONEXIÓN
    $pdo = obtenerConexionBD();
    
    // ✅ CONSULTAR PACIENTE ACTIVO EN EL BOX (usando tu estructura real)
    $sql = "SELECT 
                p.id,
                p.box,
                p.numero_box,
                p.nombre_completo,
                p.edad,
                p.peso,
                p.numero_historia,
                p.numero_hoja,
                p.fecha_ingreso,
                p.fecha_alta,
                p.estado,
                p.fecha_creacion
            FROM pacientes p 
            WHERE p.box = :box 
            AND p.estado = 'activo' 
            AND (p.fecha_alta IS NULL OR p.fecha_alta = '0000-00-00 00:00:00')
            ORDER BY p.fecha_ingreso DESC 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':box', $box, PDO::PARAM_INT);
    $stmt->execute();
    
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($paciente) {
        // ✅ PACIENTE ENCONTRADO - PROCESAR DATOS
        
        // Convertir campos numéricos
        $paciente['id'] = (int)$paciente['id'];
        $paciente['box'] = (int)$paciente['box'];
        $paciente['edad'] = (int)$paciente['edad'];
        $paciente['peso'] = $paciente['peso'] ? (float)$paciente['peso'] : null;
        
        // Añadir campo 'activo' para compatibilidad con tu JavaScript
        $paciente['activo'] = ($paciente['estado'] === 'activo');
        
        // Formatear fechas
        if ($paciente['fecha_ingreso']) {
            $fecha = new DateTime($paciente['fecha_ingreso']);
            $paciente['fecha_ingreso'] = $fecha->format('Y-m-d H:i:s');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $paciente,
            'message' => 'Paciente encontrado',
            'debug_info' => [
                'box_consultado' => $box,
                'estado_paciente' => $paciente['estado'],
                'fecha_ingreso' => $paciente['fecha_ingreso']
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // ✅ NO HAY PACIENTE ACTIVO EN ESTE BOX
        
        // Verificar si hay pacientes en cualquier estado en este box
        $sqlHistorial = "SELECT 
                            COUNT(*) as total,
                            MAX(fecha_ingreso) as ultimo_ingreso,
                            estado
                        FROM pacientes 
                        WHERE box = :box 
                        GROUP BY estado";
        $stmtHistorial = $pdo->prepare($sqlHistorial);
        $stmtHistorial->bindParam(':box', $box, PDO::PARAM_INT);
        $stmtHistorial->execute();
        $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => false,
            'data' => null,
            'message' => "No hay paciente activo en box $box",
            'box_vacio' => true,
            'historial' => $historial,
            'debug_info' => [
                'box_consultado' => $box,
                'consulta_ejecutada' => $sql
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    error_log("Error DB en obtener_paciente.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_type' => 'database',
        'debug_info' => [
            'box_solicitado' => $box ?? 'no definido',
            'archivo' => __FILE__
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en obtener_paciente.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'validation',
        'debug_info' => [
            'parametros_recibidos' => $_GET,
            'archivo' => __FILE__
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>