<?php
/**
 * API para gestión de pérdidas y balances (Sección 3)
 * Endpoints: GET, POST, PUT, DELETE /api/perdidas.php
 */

require_once '../database.php';

// Validar método HTTP
$method = validateMethod(['GET', 'POST', 'PUT', 'DELETE']);

// Crear instancia de base de datos
$db = new Database();

try {
    switch($method) {
        case 'GET':
            handleGet($db);
            break;
            
        case 'POST':
            handlePost($db);
            break;
            
        case 'PUT':
            handlePut($db);
            break;
            
        case 'DELETE':
            handleDelete($db);
            break;
    }
} catch(Exception $e) {
    logAction("ERROR_PERDIDAS", $e->getMessage());
    ApiResponse::json(ApiResponse::error($e->getMessage()));
}

/**
 * GET - Obtener datos de pérdidas
 */
function handleGet($db) {
    $pacienteId = $_GET['paciente_id'] ?? null;
    $hora = $_GET['hora'] ?? null;
    $formato = $_GET['formato'] ?? 'array';
    $incluir_balances = $_GET['incluir_balances'] ?? 'true';
    
    if(!$pacienteId) {
        ApiResponse::json(ApiResponse::error("ID de paciente requerido", 400));
    }
    
    // Verificar que el paciente existe
    if(!$db->exists('pacientes', 'id = :id', [':id' => $pacienteId])) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    if($hora !== null) {
        // Obtener datos de una hora específica
        $datos = $db->getAll(
            'perdidas',
            'paciente_id = :paciente_id AND hora = :hora',
            [':paciente_id' => $pacienteId, ':hora' => $hora]
        );
        
        $resultado = !empty($datos) ? formatearDatosHora($datos[0]) : null;
        ApiResponse::json(ApiResponse::success($resultado, "Datos obtenidos"));
        return;
    }
    
    // Obtener todos los datos del paciente
    $datos = $db->getAll(
        'perdidas',
        'paciente_id = :paciente_id',
        [':paciente_id' => $pacienteId],
        'hora ASC'
    );
    
    if($formato === 'array') {
        // Formato compatible con JavaScript section3Data
        $section3Data = [
            'diuresis' => array_fill(0, 24, ''),
            'deposiciones' => array_fill(0, 24, ''),
            'vomitos' => array_fill(0, 24, ''),
            'sng' => array_fill(0, 24, ''),
            'controlResiduos' => array_fill(0, 24, ''),
            'fiebreTqn' => array_fill(0, 24, 0),
            'drenajes' => array_fill(0, 24, 0),
            'drenaje1' => array_fill(0, 24, ''),
            'drenaje2' => array_fill(0, 24, ''),
            'drenaje3' => array_fill(0, 24, ''),
            'drenaje4' => array_fill(0, 24, ''),
            'drenaje5' => array_fill(0, 24, ''),
            'totals' => array_fill(0, 24, 0)
        ];
        
        foreach($datos as $dato) {
            $hora = (int)$dato['hora'];
            
            $section3Data['diuresis'][$hora] = $dato['diuresis'] ?? '';
            $section3Data['deposiciones'][$hora] = $dato['deposiciones'] ?? '';
            $section3Data['vomitos'][$hora] = $dato['vomitos'] ?? '';
            $section3Data['sng'][$hora] = $dato['sng'] ?? '';
            $section3Data['controlResiduos'][$hora] = $dato['control_residuos'] ?? '';
            $section3Data['fiebreTqn'][$hora] = (float)($dato['fiebre_tqn'] ?? 0);
            $section3Data['drenajes'][$hora] = (float)($dato['drenajes_total'] ?? 0);
            $section3Data['drenaje1'][$hora] = $dato['drenaje_1'] ?? '';
            $section3Data['drenaje2'][$hora] = $dato['drenaje_2'] ?? '';
            $section3Data['drenaje3'][$hora] = $dato['drenaje_3'] ?? '';
            $section3Data['drenaje4'][$hora] = $dato['drenaje_4'] ?? '';
            $section3Data['drenaje5'][$hora] = $dato['drenaje_5'] ?? '';
        }
        
        // Incluir balances si se solicita
        if($incluir_balances === 'true') {
            $balances = obtenerBalances($db, $pacienteId);
            $section3Data['balances'] = $balances;
        }
        
        ApiResponse::json(ApiResponse::success($section3Data, "Datos obtenidos en formato array"));
    } else {
        // Formato objeto con metadatos
        $resultado = [
            'paciente_id' => $pacienteId,
            'total_registros' => count($datos),
            'horas_con_datos' => array_column($datos, 'hora'),
            'datos' => array_map('formatearDatosHora', $datos)
        ];
        
        if($incluir_balances === 'true') {
            $resultado['balances'] = obtenerBalances($db, $pacienteId);
        }
        
        ApiResponse::json(ApiResponse::success($resultado, "Datos obtenidos en formato objeto"));
    }
}

/**
 * POST - Crear o actualizar datos de una hora
 */
function handlePost($db) {
    $data = getJsonInput();
    
    // Validar campos requeridos
    validateRequired($data, ['paciente_id', 'hora']);
    
    $pacienteId = $data['paciente_id'];
    $hora = $data['hora'];
    
    // Validar hora
    if($hora < 0 || $hora > 23) {
        ApiResponse::json(ApiResponse::error("Hora debe estar entre 0 y 23", 400));
    }
    
    // Verificar que el paciente existe
    if(!$db->exists('pacientes', 'id = :id', [':id' => $pacienteId])) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    // Obtener datos del paciente para cálculos
    $paciente = $db->getById('pacientes', $pacienteId);
    $peso = (float)($paciente['peso'] ?? 0);
    
    // Preparar datos para inserción/actualización
    $perdidasData = [
        'paciente_id' => $pacienteId,
        'hora' => $hora,
        'diuresis' => validarVolumen($data['diuresis'] ?? 0),
        'deposiciones' => validarVolumen($data['deposiciones'] ?? 0),
        'vomitos' => validarVolumen($data['vomitos'] ?? 0),
        'sng' => validarVolumen($data['sng'] ?? 0),
        'control_residuos' => validarVolumen($data['control_residuos'] ?? 0),
        'drenaje_1' => validarVolumen($data['drenaje_1'] ?? 0),
        'drenaje_2' => validarVolumen($data['drenaje_2'] ?? 0),
        'drenaje_3' => validarVolumen($data['drenaje_3'] ?? 0),
        'drenaje_4' => validarVolumen($data['drenaje_4'] ?? 0),
        'drenaje_5' => validarVolumen($data['drenaje_5'] ?? 0)
    ];
    
    // Calcular valores automáticos
    $perdidasData['drenajes_total'] = 
        $perdidasData['drenaje_1'] + 
        $perdidasData['drenaje_2'] + 
        $perdidasData['drenaje_3'] + 
        $perdidasData['drenaje_4'] + 
        $perdidasData['drenaje_5'];
    
    // Calcular fiebre y taquipnea si hay constantes vitales para esta hora
    $perdidasData['fiebre_tqn'] = calcularFiebreTqn($db, $pacienteId, $hora, $peso);
    
    // Verificar si ya existe registro para esta hora
    $existeRegistro = $db->exists(
        'perdidas',
        'paciente_id = :paciente_id AND hora = :hora',
        [':paciente_id' => $pacienteId, ':hora' => $hora]
    );
    
    if($existeRegistro) {
        // Actualizar registro existente
        $db->update(
            'perdidas',
            $perdidasData,
            'paciente_id = :paciente_id AND hora = :hora',
            [':paciente_id' => $pacienteId, ':hora' => $hora]
        );
        
        $mensaje = "Datos actualizados correctamente";
    } else {
        // Crear nuevo registro
        $db->insert('perdidas', $perdidasData);
        $mensaje = "Datos creados correctamente";
    }
    
    // Recalcular balances
    recalcularBalances($db, $pacienteId);
    
    // Obtener el registro actualizado/creado
    $datosActualizados = $db->getAll(
        'perdidas',
        'paciente_id = :paciente_id AND hora = :hora',
        [':paciente_id' => $pacienteId, ':hora' => $hora]
    );
    
    logAction("GUARDAR_PERDIDAS", [
        'paciente_id' => $pacienteId,
        'hora' => $hora,
        'accion' => $existeRegistro ? 'actualizar' : 'crear'
    ]);
    
    ApiResponse::json(ApiResponse::success(formatearDatosHora($datosActualizados[0]), $mensaje));
}

/**
 * PUT - Guardar múltiples horas de datos
 */
function handlePut($db) {
    $data = getJsonInput();
    
    // Validar campos requeridos
    validateRequired($data, ['paciente_id', 'section3Data']);
    
    $pacienteId = $data['paciente_id'];
    $section3Data = $data['section3Data'];
    
    // Verificar que el paciente existe
    if(!$db->exists('pacientes', 'id = :id', [':id' => $pacienteId])) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    // Obtener datos del paciente para cálculos
    $paciente = $db->getById('pacientes', $pacienteId);
    $peso = (float)($paciente['peso'] ?? 0);
    
    $operaciones = [];
    $resultados = [];
    
    // Procesar cada hora
    for($hora = 0; $hora < 24; $hora++) {
        try {
            // Extraer datos de esta hora
            $valores = [
                'diuresis' => $section3Data['diuresis'][$hora] ?? 0,
                'deposiciones' => $section3Data['deposiciones'][$hora] ?? 0,
                'vomitos' => $section3Data['vomitos'][$hora] ?? 0,
                'sng' => $section3Data['sng'][$hora] ?? 0,
                'control_residuos' => $section3Data['controlResiduos'][$hora] ?? 0,
                'drenaje_1' => $section3Data['drenaje1'][$hora] ?? 0,
                'drenaje_2' => $section3Data['drenaje2'][$hora] ?? 0,
                'drenaje_3' => $section3Data['drenaje3'][$hora] ?? 0,
                'drenaje_4' => $section3Data['drenaje4'][$hora] ?? 0,
                'drenaje_5' => $section3Data['drenaje5'][$hora] ?? 0
            ];
            
            // Verificar si hay algún dato válido para esta hora
            $tieneValores = false;
            foreach($valores as $valor) {
                if($valor !== null && $valor !== '' && $valor != 0) {
                    $tieneValores = true;
                    break;
                }
            }
            
            if(!$tieneValores) {
                continue; // Saltar esta hora si no hay datos
            }
            
            $perdidasData = [
                'paciente_id' => $pacienteId,
                'hora' => $hora,
                'diuresis' => validarVolumen($valores['diuresis']),
                'deposiciones' => validarVolumen($valores['deposiciones']),
                'vomitos' => validarVolumen($valores['vomitos']),
                'sng' => validarVolumen($valores['sng']),
                'control_residuos' => validarVolumen($valores['control_residuos']),
                'drenaje_1' => validarVolumen($valores['drenaje_1']),
                'drenaje_2' => validarVolumen($valores['drenaje_2']),
                'drenaje_3' => validarVolumen($valores['drenaje_3']),
                'drenaje_4' => validarVolumen($valores['drenaje_4']),
                'drenaje_5' => validarVolumen($valores['drenaje_5'])
            ];
            
            // Calcular valores automáticos
            $perdidasData['drenajes_total'] = 
                $perdidasData['drenaje_1'] + 
                $perdidasData['drenaje_2'] + 
                $perdidasData['drenaje_3'] + 
                $perdidasData['drenaje_4'] + 
                $perdidasData['drenaje_5'];
            
            $perdidasData['fiebre_tqn'] = calcularFiebreTqn($db, $pacienteId, $hora, $peso);
            
            // Verificar si existe el registro
            $existe = $db->exists(
                'perdidas',
                'paciente_id = :paciente_id AND hora = :hora',
                [':paciente_id' => $pacienteId, ':hora' => $hora]
            );
            
            if($existe) {
                $operaciones[] = [
                    'method' => 'update',
                    'params' => [
                        'perdidas',
                        $perdidasData,
                        'paciente_id = :paciente_id AND hora = :hora',
                        [':paciente_id' => $pacienteId, ':hora' => $hora]
                    ]
                ];
            } else {
                $operaciones[] = [
                    'method' => 'insert',
                    'params' => ['perdidas', $perdidasData]
                ];
            }
            
            $resultados[] = [
                'hora' => $hora,
                'accion' => $existe ? 'actualizado' : 'creado'
            ];
            
        } catch(Exception $e) {
            $resultados[] = [
                'hora' => $hora,
                'error' => $e->getMessage()
            ];
        }
    }
    
    if(empty($operaciones)) {
        ApiResponse::json(ApiResponse::error("No hay datos válidos para guardar", 400));
    }
    
    // Ejecutar todas las operaciones en una transacción
    try {
        $db->transaction($operaciones);
        
        // Recalcular balances después de guardar todos los datos
        recalcularBalances($db, $pacienteId);
        
        logAction("GUARDAR_MULTIPLE_PERDIDAS", [
            'paciente_id' => $pacienteId,
            'operaciones' => count($operaciones)
        ]);
        
        // Obtener balances actualizados
        $balances = obtenerBalances($db, $pacienteId);
        $resultados['balances_actualizados'] = $balances;
        
        ApiResponse::json(ApiResponse::success($resultados, "Datos guardados correctamente"));
        
    } catch(Exception $e) {
        ApiResponse::json(ApiResponse::error("Error al guardar datos: " . $e->getMessage()));
    }
}

/**
 * DELETE - Eliminar datos de una hora específica
 */
function handleDelete($db) {
    $pacienteId = $_GET['paciente_id'] ?? null;
    $hora = $_GET['hora'] ?? null;
    
    if(!$pacienteId || $hora === null) {
        ApiResponse::json(ApiResponse::error("ID de paciente y hora requeridos", 400));
    }
    
    // Validar hora
    if($hora < 0 || $hora > 23) {
        ApiResponse::json(ApiResponse::error("Hora debe estar entre 0 y 23", 400));
    }
    
    // Verificar que el registro existe
    if(!$db->exists('perdidas', 'paciente_id = :paciente_id AND hora = :hora', 
                   [':paciente_id' => $pacienteId, ':hora' => $hora])) {
        ApiResponse::json(ApiResponse::error("Registro no encontrado", 404));
    }
    
    // Eliminar registro
    $db->delete(
        'perdidas',
        'paciente_id = :paciente_id AND hora = :hora',
        [':paciente_id' => $pacienteId, ':hora' => $hora]
    );
    
    // Recalcular balances
    recalcularBalances($db, $pacienteId);
    
    logAction("ELIMINAR_PERDIDAS", [
        'paciente_id' => $pacienteId,
        'hora' => $hora
    ]);
    
    ApiResponse::json(ApiResponse::success(null, "Datos eliminados correctamente"));
}

/**
 * Funciones auxiliares
 */
function validarVolumen($valor) {
    if($valor === null || $valor === '') {
        return 0;
    }
    
    $valor = (float)$valor;
    
    if($valor < 0 || $valor > 9999) {
        throw new Exception("Volumen $valor fuera del rango permitido (0-9999)");
    }
    
    return $valor;
}

function formatearDatosHora($dato) {
    return [
        'id' => $dato['id'],
        'paciente_id' => $dato['paciente_id'],
        'hora' => (int)$dato['hora'],
        'diuresis' => (float)$dato['diuresis'],
        'deposiciones' => (float)$dato['deposiciones'],
        'vomitos' => (float)$dato['vomitos'],
        'sng' => (float)$dato['sng'],
        'control_residuos' => (float)$dato['control_residuos'],
        'fiebre_tqn' => (float)$dato['fiebre_tqn'],
        'drenajes_total' => (float)$dato['drenajes_total'],
        'drenaje_1' => (float)$dato['drenaje_1'],
        'drenaje_2' => (float)$dato['drenaje_2'],
        'drenaje_3' => (float)$dato['drenaje_3'],
        'drenaje_4' => (float)$dato['drenaje_4'],
        'drenaje_5' => (float)$dato['drenaje_5'],
        'fecha_registro' => $dato['fecha_registro']
    ];
}

function calcularFiebreTqn($db, $pacienteId, $hora, $peso) {
    if($peso <= 0) return 0;
    
    // Obtener constantes vitales de esta hora
    $constantes = $db->getAll(
        'constantes_vitales',
        'paciente_id = :paciente_id AND hora = :hora',
        [':paciente_id' => $pacienteId, ':hora' => $hora]
    );
    
    if(empty($constantes)) return 0;
    
    $constante = $constantes[0];
    $temperatura = (float)($constante['temperatura'] ?? 0);
    $frecuenciaResp = (float)($constante['frecuencia_respiratoria'] ?? 0);
    
    $total = 0;
    
    // Cálculos de FIEBRE
    if($temperatura > 39) {
        $total += 0.3 * $peso;
    } elseif($temperatura > 38) {
        $total += 0.2 * $peso;
    } elseif($temperatura > 37) {
        $total += 0.1 * $peso;
    }
    
    // Cálculos de TQN (Taquipnea)
    if($frecuenciaResp > 35) {
        $total += 0.3 * $peso;
    } elseif($frecuenciaResp > 25) {
        $total += 0.2 * $peso;
    }
    
    return round($total, 1);
}

function calcularPerdidasInsensibles($peso, $horasIngreso) {
    if($peso <= 0) return 0;
    
    // Fórmula: 0.5 × PESO × Nº HORAS
    return round(0.5 * $peso * $horasIngreso, 1);
}

function calcularHorasIngreso($fechaIngreso) {
    if(!$fechaIngreso) return 24;
    
    $ingreso = new DateTime($fechaIngreso);
    $ahora = new DateTime();
    
    // Aplicar lógica de día clínico (cambia a las 8:00)
    if($ahora->format('H') < 8) {
        $ahora->modify('-1 day');
    }
    
    if($ingreso->format('H') < 8) {
        $ingreso->modify('-1 day');
    }
    
    $diff = $ahora->diff($ingreso);
    return min(24, max(0, $diff->h + ($diff->days * 24)));
}

function recalcularBalances($db, $pacienteId) {
    // Obtener datos del paciente
    $paciente = $db->getById('pacientes', $pacienteId);
    if(!$paciente) return;
    
    $peso = (float)($paciente['peso'] ?? 0);
    $horasIngreso = calcularHorasIngreso($paciente['fecha_ingreso']);
    
    // Calcular balances desde los datos de pérdidas
    $query = "
        SELECT 
            COALESCE(SUM(diuresis), 0) as balance_diuresis,
            COALESCE(SUM(deposiciones), 0) as balance_deposiciones,
            COALESCE(SUM(vomitos), 0) as balance_vomitos,
            COALESCE(SUM(fiebre_tqn), 0) as balance_fiebre_tqn,
            COALESCE(SUM(sng), 0) as balance_sng,
            COALESCE(SUM(drenajes_total), 0) as balance_drenajes,
            COALESCE(SUM(control_residuos), 0) as balance_control_residuos,
            COALESCE(SUM(drenaje_1), 0) as balance_drenaje_1,
            COALESCE(SUM(drenaje_2), 0) as balance_drenaje_2,
            COALESCE(SUM(drenaje_3), 0) as balance_drenaje_3,
            COALESCE(SUM(drenaje_4), 0) as balance_drenaje_4,
            COALESCE(SUM(drenaje_5), 0) as balance_drenaje_5
        FROM perdidas 
        WHERE paciente_id = :paciente_id
    ";
    
    $stmt = $db->executeQuery($query, [':paciente_id' => $pacienteId]);
    $balances = $stmt->fetch();
    
    // Calcular pérdidas insensibles
    $perdidasInsensibles = calcularPerdidasInsensibles($peso, $horasIngreso);
    $balances['balance_perdidas_insensibles'] = $perdidasInsensibles;
    
    // Calcular balance total
    $balances['balance_total'] = 
        $balances['balance_diuresis'] +
        $balances['balance_deposiciones'] +
        $balances['balance_vomitos'] +
        $balances['balance_fiebre_tqn'] +
        $balances['balance_sng'] +
        $balances['balance_drenajes'] +
        $balances['balance_control_residuos'] +
        $balances['balance_perdidas_insensibles'];
    
    $balances['paciente_id'] = $pacienteId;
    
    // Insertar o actualizar en tabla de balances
    $existeBalance = $db->exists('balances_diarios', 'paciente_id = :id', [':id' => $pacienteId]);
    
    if($existeBalance) {
        unset($balances['paciente_id']);
        $db->update('balances_diarios', $balances, 'paciente_id = :id', [':id' => $pacienteId]);
    } else {
        $db->insert('balances_diarios', $balances);
    }
    
    return $balances;
}

function obtenerBalances($db, $pacienteId) {
    $balances = $db->getAll('balances_diarios', 'paciente_id = :id', [':id' => $pacienteId]);
    
    if(empty($balances)) {
        // Si no existen balances, calcularlos
        return recalcularBalances($db, $pacienteId);
    }
    
    return $balances[0];
}

/**
 * Endpoint para recalcular balances manualmente
 */
if(isset($_GET['recalcular_balances'])) {
    $pacienteId = $_GET['paciente_id'] ?? null;
    
    if(!$pacienteId) {
        ApiResponse::json(ApiResponse::error("ID de paciente requerido", 400));
    }
    
    if(!$db->exists('pacientes', 'id = :id', [':id' => $pacienteId])) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    $balances = recalcularBalances($db, $pacienteId);
    
    logAction("RECALCULAR_BALANCES", ['paciente_id' => $pacienteId]);
    
    ApiResponse::json(ApiResponse::success($balances, "Balances recalculados correctamente"));
}

/**
 * Endpoint para obtener solo balances
 */
if(isset($_GET['solo_balances'])) {
    $pacienteId = $_GET['paciente_id'] ?? null;
    
    if(!$pacienteId) {
        ApiResponse::json(ApiResponse::error("ID de paciente requerido", 400));
    }
    
    if(!$db->exists('pacientes', 'id = :id', [':id' => $pacienteId])) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    $balances = obtenerBalances($db, $pacienteId);
    
    ApiResponse::json(ApiResponse::success($balances, "Balances obtenidos"));
}
?>