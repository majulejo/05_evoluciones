<?php
/**
 * API para sincronización general entre localStorage y base de datos
 * Endpoints centralizados para sincronizar todas las secciones
 */

require_once '../database.php';

// Validar método HTTP
$method = validateMethod(['GET', 'POST', 'PUT']);

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
    }
} catch(Exception $e) {
    logAction("ERROR_SYNC", $e->getMessage());
    ApiResponse::json(ApiResponse::error($e->getMessage()));
}

/**
 * GET - Obtener todos los datos de un paciente para cargar en el frontend
 */
function handleGet($db) {
    $pacienteId = $_GET['paciente_id'] ?? null;
    $cama = $_GET['cama'] ?? null;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    
    if(!$pacienteId && !$cama) {
        ApiResponse::json(ApiResponse::error("ID de paciente o número de cama requerido", 400));
    }
    
    // Si se proporciona cama en lugar de ID, buscar el paciente
    if($cama && !$pacienteId) {
        $pacientes = $db->getAll(
            'pacientes', 
            'cama = :cama AND fecha_grafica = :fecha AND activo = 1',
            [':cama' => $cama, ':fecha' => $fecha],
            'id DESC',
            '1'
        );
        
        if(empty($pacientes)) {
            ApiResponse::json(ApiResponse::error("No hay paciente activo en esa cama para la fecha especificada", 404));
        }
        
        $pacienteId = $pacientes[0]['id'];
    }
    
    // Verificar que el paciente existe
    $paciente = $db->getById('pacientes', $pacienteId);
    if(!$paciente) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    // Obtener todos los datos del paciente
    $datosCompletos = [
        'paciente' => $paciente,
        'vitalSigns' => obtenerConstantesVitalesArray($db, $pacienteId),
        'section2Data' => obtenerSection2Array($db, $pacienteId),
        'section3Data' => obtenerSection3Array($db, $pacienteId),
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0'
    ];
    
    logAction("CARGAR_DATOS_COMPLETOS", ['paciente_id' => $pacienteId]);
    
    ApiResponse::json(ApiResponse::success($datosCompletos, "Datos completos cargados correctamente"));
}

/**
 * POST - Sincronizar datos desde localStorage a base de datos
 */
function handlePost($db) {
    $data = getJsonInput();
    
    validateRequired($data, ['paciente_id']);
    
    $pacienteId = $data['paciente_id'];
    
    // Verificar que el paciente existe
    if(!$db->exists('pacientes', 'id = :id', [':id' => $pacienteId])) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    $resultados = [
        'paciente_id' => $pacienteId,
        'timestamp' => date('Y-m-d H:i:s'),
        'sincronizaciones' => [],
        'errores' => [],
        'total_operaciones' => 0
    ];
    
    // Sincronizar datos del paciente si se proporcionan
    if(isset($data['paciente_data'])) {
        try {
            $pacienteData = $data['paciente_data'];
            unset($pacienteData['id']); // No permitir cambiar el ID
            
            $db->update('pacientes', $pacienteData, 'id = :id', [':id' => $pacienteId]);
            
            $resultados['sincronizaciones'][] = [
                'seccion' => 'paciente',
                'estado' => 'actualizado',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $resultados['total_operaciones']++;
            
        } catch(Exception $e) {
            $resultados['errores'][] = [
                'seccion' => 'paciente',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Sincronizar constantes vitales si se proporcionan
    if(isset($data['vitalSigns']) && is_array($data['vitalSigns'])) {
        $resultado = sincronizarConstantesVitales($db, $pacienteId, $data['vitalSigns']);
        $resultados['sincronizaciones'][] = $resultado;
        $resultados['total_operaciones'] += $resultado['registros_procesados'];
    }
    
    // Sincronizar sección 2 si se proporciona
    if(isset($data['section2Data'])) {
        $resultado = sincronizarSection2($db, $pacienteId, $data['section2Data']);
        $resultados['sincronizaciones'][] = $resultado;
        $resultados['total_operaciones'] += $resultado['registros_procesados'];
    }
    
    // Sincronizar sección 3 si se proporciona
    if(isset($data['section3Data'])) {
        $resultado = sincronizarSection3($db, $pacienteId, $data['section3Data']);
        $resultados['sincronizaciones'][] = $resultado;
        $resultados['total_operaciones'] += $resultado['registros_procesados'];
    }
    
    logAction("SINCRONIZACION_COMPLETA", $resultados);
    
    $mensaje = count($resultados['errores']) > 0 
        ? "Sincronización completada con algunos errores" 
        : "Sincronización completada exitosamente";
    
    ApiResponse::json(ApiResponse::success($resultados, $mensaje));
}

/**
 * PUT - Backup completo de todos los datos de un paciente
 */
function handlePut($db) {
    $data = getJsonInput();
    
    validateRequired($data, ['paciente_id', 'backup_data']);
    
    $pacienteId = $data['paciente_id'];
    $backupData = $data['backup_data'];
    
    // Verificar que el paciente existe
    if(!$db->exists('pacientes', 'id = :id', [':id' => $pacienteId])) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    $operaciones = [];
    $resultados = [];
    
    try {
        // Iniciar transacción para backup completo
        $conn = $db->getConnection();
        $conn->beginTransaction();
        
        // Limpiar datos existentes del paciente
        $tablas = ['constantes_vitales', 'oxigenacion_dolor', 'perdidas'];
        foreach($tablas as $tabla) {
            $db->delete($tabla, 'paciente_id = :id', [':id' => $pacienteId]);
        }
        
        // Restaurar constantes vitales
        if(isset($backupData['vitalSigns'])) {
            $resultado = restaurarConstantesVitales($db, $pacienteId, $backupData['vitalSigns']);
            $resultados[] = $resultado;
        }
        
        // Restaurar sección 2
        if(isset($backupData['section2Data'])) {
            $resultado = restaurarSection2($db, $pacienteId, $backupData['section2Data']);
            $resultados[] = $resultado;
        }
        
        // Restaurar sección 3
        if(isset($backupData['section3Data'])) {
            $resultado = restaurarSection3($db, $pacienteId, $backupData['section3Data']);
            $resultados[] = $resultado;
        }
        
        $conn->commit();
        
        logAction("BACKUP_RESTAURADO", [
            'paciente_id' => $pacienteId,
            'operaciones' => count($resultados)
        ]);
        
        ApiResponse::json(ApiResponse::success($resultados, "Backup restaurado correctamente"));
        
    } catch(Exception $e) {
        $conn->rollback();
        ApiResponse::json(ApiResponse::error("Error al restaurar backup: " . $e->getMessage()));
    }
}

/**
 * Funciones auxiliares para sincronización
 */
function obtenerConstantesVitalesArray($db, $pacienteId) {
    $constantes = $db->getAll(
        'constantes_vitales',
        'paciente_id = :id',
        [':id' => $pacienteId],
        'hora ASC'
    );
    
    $vitalSigns = [];
    for($i = 0; $i < 24; $i++) {
        $vitalSigns[$i] = [];
    }
    
    foreach($constantes as $constante) {
        $hora = (int)$constante['hora'];
        $vitalSigns[$hora] = [
            'respRate' => $constante['frecuencia_respiratoria'] ? (float)$constante['frecuencia_respiratoria'] : null,
            'temperature' => $constante['temperatura'] ? (float)$constante['temperatura'] : null,
            'pulse' => $constante['frecuencia_cardiaca'] ? (int)$constante['frecuencia_cardiaca'] : null,
            'systolic' => $constante['tension_sistolica'] ? (int)$constante['tension_sistolica'] : null,
            'diastolic' => $constante['tension_diastolica'] ? (int)$constante['tension_diastolica'] : null,
            'satO2' => $constante['saturacion_oxigeno'] ? (float)$constante['saturacion_oxigeno'] : null,
            'glucemia' => $constante['glucemia'] ? (int)$constante['glucemia'] : null
        ];
    }
    
    return $vitalSigns;
}

function obtenerSection2Array($db, $pacienteId) {
    $datos = $db->getAll(
        'oxigenacion_dolor',
        'paciente_id = :id',
        [':id' => $pacienteId],
        'hora ASC'
    );
    
    $section2Data = [
        'pneumo' => array_fill(0, 24, ''),
        'oxygen' => array_fill(0, 24, ''),
        'saturation' => array_fill(0, 24, ''),
        'eva' => array_fill(0, 24, ['eva' => '', 'rass' => '']),
        'glucose' => array_fill(0, 24, ''),
        'insulin' => array_fill(0, 24, [
            'value' => '',
            'type' => 'S/P',
            'recommended' => '',
            'message' => ''
        ])
    ];
    
    foreach($datos as $dato) {
        $hora = (int)$dato['hora'];
        
        $section2Data['pneumo'][$hora] = $dato['neumotorax_porcentaje'] ?? '';
        $section2Data['oxygen'][$hora] = $dato['tipo_oxigeno'] ?? '';
        $section2Data['saturation'][$hora] = $dato['saturacion_manual'] ?? '';
        $section2Data['eva'][$hora] = [
            'eva' => $dato['eva_dolor'] ?? '',
            'rass' => $dato['rass_sedacion'] ?? ''
        ];
        $section2Data['glucose'][$hora] = $dato['glucemia_manual'] ?? '';
        $section2Data['insulin'][$hora] = [
            'value' => $dato['insulina_valor'] ?? '',
            'type' => $dato['insulina_tipo'] ?? 'S/P',
            'recommended' => $dato['insulina_recomendada'] ?? '',
            'message' => $dato['insulina_mensaje'] ?? ''
        ];
    }
    
    return $section2Data;
}

function obtenerSection3Array($db, $pacienteId) {
    $datos = $db->getAll(
        'perdidas',
        'paciente_id = :id',
        [':id' => $pacienteId],
        'hora ASC'
    );
    
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
        'totals' => array_fill(0, 24, 0),
        'drenajesExpanded' => false
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
    
    // Obtener balances
    $balances = $db->getAll('balances_diarios', 'paciente_id = :id', [':id' => $pacienteId]);
    if(!empty($balances)) {
        $balance = $balances[0];
        $section3Data['balances'] = [
            'diuresis' => (float)$balance['balance_diuresis'],
            'deposiciones' => (float)$balance['balance_deposiciones'],
            'vomitos' => (float)$balance['balance_vomitos'],
            'fiebreTqn' => (float)$balance['balance_fiebre_tqn'],
            'sng' => (float)$balance['balance_sng'],
            'drenajes' => (float)$balance['balance_drenajes'],
            'controlResiduos' => (float)$balance['balance_control_residuos'],
            'perdidasInsensibles' => (float)$balance['balance_perdidas_insensibles'],
            'drenaje1' => (float)$balance['balance_drenaje_1'],
            'drenaje2' => (float)$balance['balance_drenaje_2'],
            'drenaje3' => (float)$balance['balance_drenaje_3'],
            'drenaje4' => (float)$balance['balance_drenaje_4'],
            'drenaje5' => (float)$balance['balance_drenaje_5'],
            'total' => (float)$balance['balance_total']
        ];
    } else {
        $section3Data['balances'] = [
            'diuresis' => 0, 'deposiciones' => 0, 'vomitos' => 0, 'fiebreTqn' => 0,
            'sng' => 0, 'drenajes' => 0, 'controlResiduos' => 0, 'perdidasInsensibles' => 0,
            'drenaje1' => 0, 'drenaje2' => 0, 'drenaje3' => 0, 'drenaje4' => 0, 'drenaje5' => 0,
            'total' => 0
        ];
    }
    
    return $section3Data;
}

function sincronizarConstantesVitales($db, $pacienteId, $vitalSigns) {
    $sincronizados = 0;
    $errores = [];
    
    for($hora = 0; $hora < 24; $hora++) {
        if(!isset($vitalSigns[$hora]) || empty($vitalSigns[$hora])) {
            continue;
        }
        
        $constantes = $vitalSigns[$hora];
        
        // Verificar si hay datos válidos
        $tieneValores = false;
        foreach($constantes as $valor) {
            if($valor !== null && $valor !== '') {
                $tieneValores = true;
                break;
            }
        }
        
        if(!$tieneValores) {
            continue;
        }
        
        try {
            $constantesData = [
                'paciente_id' => $pacienteId,
                'hora' => $hora,
                'frecuencia_respiratoria' => validarRangoConstantes($constantes['respRate'] ?? null, 0, 50),
                'temperatura' => validarRangoConstantes($constantes['temperature'] ?? null, 32, 42),
                'frecuencia_cardiaca' => validarRangoConstantes($constantes['pulse'] ?? null, 0, 200),
                'tension_sistolica' => validarRangoConstantes($constantes['systolic'] ?? null, 0, 250),
                'tension_diastolica' => validarRangoConstantes($constantes['diastolic'] ?? null, 0, 250),
                'saturacion_oxigeno' => validarRangoConstantes($constantes['satO2'] ?? null, 0, 100),
                'glucemia' => validarRangoConstantes($constantes['glucemia'] ?? null, 0, 600)
            ];
            
            // Upsert
            $existe = $db->exists(
                'constantes_vitales',
                'paciente_id = :paciente_id AND hora = :hora',
                [':paciente_id' => $pacienteId, ':hora' => $hora]
            );
            
            if($existe) {
                $db->update(
                    'constantes_vitales',
                    $constantesData,
                    'paciente_id = :paciente_id AND hora = :hora',
                    [':paciente_id' => $pacienteId, ':hora' => $hora]
                );
            } else {
                $db->insert('constantes_vitales', $constantesData);
            }
            
            $sincronizados++;
            
        } catch(Exception $e) {
            $errores[] = "Hora $hora: " . $e->getMessage();
        }
    }
    
    return [
        'seccion' => 'constantes_vitales',
        'registros_procesados' => $sincronizados,
        'errores' => $errores,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function sincronizarSection2($db, $pacienteId, $section2Data) {
    $sincronizados = 0;
    $errores = [];
    
    for($hora = 0; $hora < 24; $hora++) {
        try {
            $valores = [
                'pneumo' => $section2Data['pneumo'][$hora] ?? '',
                'oxygen' => $section2Data['oxygen'][$hora] ?? '',
                'saturation' => $section2Data['saturation'][$hora] ?? '',
                'eva' => $section2Data['eva'][$hora]['eva'] ?? '',
                'rass' => $section2Data['eva'][$hora]['rass'] ?? '',
                'glucose' => $section2Data['glucose'][$hora] ?? '',
                'insulin' => $section2Data['insulin'][$hora] ?? []
            ];
            
            // Verificar si hay datos válidos
            $tieneValores = false;
            foreach(['pneumo', 'oxygen', 'saturation', 'eva', 'rass', 'glucose'] as $campo) {
                if($valores[$campo] !== null && $valores[$campo] !== '') {
                    $tieneValores = true;
                    break;
                }
            }
            
            if(isset($valores['insulin']['value']) && $valores['insulin']['value'] !== '') {
                $tieneValores = true;
            }
            
            if(!$tieneValores) {
                continue;
            }
            
            $oxigenacionData = [
                'paciente_id' => $pacienteId,
                'hora' => $hora,
                'neumotorax_porcentaje' => validarRangoOxigenacion($valores['pneumo'], 0, 100, true),
                'tipo_oxigeno' => validarTipoOxigeno($valores['oxygen']),
                'saturacion_manual' => validarRangoOxigenacion($valores['saturation'], 0, 100, true),
                'eva_dolor' => validarRangoOxigenacion($valores['eva'], 0, 10, true),
                'rass_sedacion' => validarRangoOxigenacion($valores['rass'], -5, 4, true),
                'glucemia_manual' => validarRangoOxigenacion($valores['glucose'], 0, 600, true),
                'insulina_valor' => substr(($valores['insulin']['value'] ?? ''), 0, 50),
                'insulina_tipo' => validarTipoInsulina($valores['insulin']['type'] ?? 'S/P'),
                'insulina_recomendada' => substr(($valores['insulin']['recommended'] ?? ''), 0, 50),
                'insulina_mensaje' => substr(($valores['insulin']['message'] ?? ''), 0, 255)
            ];
            
            // Upsert
            $existe = $db->exists(
                'oxigenacion_dolor',
                'paciente_id = :paciente_id AND hora = :hora',
                [':paciente_id' => $pacienteId, ':hora' => $hora]
            );
            
            if($existe) {
                $db->update(
                    'oxigenacion_dolor',
                    $oxigenacionData,
                    'paciente_id = :paciente_id AND hora = :hora',
                    [':paciente_id' => $pacienteId, ':hora' => $hora]
                );
            } else {
                $db->insert('oxigenacion_dolor', $oxigenacionData);
            }
            
            $sincronizados++;
            
        } catch(Exception $e) {
            $errores[] = "Hora $hora: " . $e->getMessage();
        }
    }
    
    return [
        'seccion' => 'oxigenacion_dolor',
        'registros_procesados' => $sincronizados,
        'errores' => $errores,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function sincronizarSection3($db, $pacienteId, $section3Data) {
    $sincronizados = 0;
    $errores = [];
    
    // Obtener peso del paciente para cálculos
    $paciente = $db->getById('pacientes', $pacienteId);
    $peso = (float)($paciente['peso'] ?? 0);
    
    for($hora = 0; $hora < 24; $hora++) {
        try {
            $valores = [
                'diuresis' => $section3Data['diuresis'][$hora] ?? 0,
                'deposiciones' => $section3Data['deposiciones'][$hora] ?? 0,
                'vomitos' => $section3Data['vomitos'][$hora] ?? 0,
                'sng' => $section3Data['sng'][$hora] ?? 0,
                'controlResiduos' => $section3Data['controlResiduos'][$hora] ?? 0,
                'drenaje1' => $section3Data['drenaje1'][$hora] ?? 0,
                'drenaje2' => $section3Data['drenaje2'][$hora] ?? 0,
                'drenaje3' => $section3Data['drenaje3'][$hora] ?? 0,
                'drenaje4' => $section3Data['drenaje4'][$hora] ?? 0,
                'drenaje5' => $section3Data['drenaje5'][$hora] ?? 0
            ];
            
            // Verificar si hay datos válidos
            $tieneValores = false;
            foreach($valores as $valor) {
                if($valor !== null && $valor !== '' && $valor != 0) {
                    $tieneValores = true;
                    break;
                }
            }
            
            if(!$tieneValores) {
                continue;
            }
            
            $perdidasData = [
                'paciente_id' => $pacienteId,
                'hora' => $hora,
                'diuresis' => validarVolumen($valores['diuresis']),
                'deposiciones' => validarVolumen($valores['deposiciones']),
                'vomitos' => validarVolumen($valores['vomitos']),
                'sng' => validarVolumen($valores['sng']),
                'control_residuos' => validarVolumen($valores['controlResiduos']),
                'drenaje_1' => validarVolumen($valores['drenaje1']),
                'drenaje_2' => validarVolumen($valores['drenaje2']),
                'drenaje_3' => validarVolumen($valores['drenaje3']),
                'drenaje_4' => validarVolumen($valores['drenaje4']),
                'drenaje_5' => validarVolumen($valores['drenaje5'])
            ];
            
            // Calcular valores automáticos
            $perdidasData['drenajes_total'] = 
                $perdidasData['drenaje_1'] + 
                $perdidasData['drenaje_2'] + 
                $perdidasData['drenaje_3'] + 
                $perdidasData['drenaje_4'] + 
                $perdidasData['drenaje_5'];
            
            $perdidasData['fiebre_tqn'] = calcularFiebreTqnSync($db, $pacienteId, $hora, $peso);
            
            // Upsert
            $existe = $db->exists(
                'perdidas',
                'paciente_id = :paciente_id AND hora = :hora',
                [':paciente_id' => $pacienteId, ':hora' => $hora]
            );
            
            if($existe) {
                $db->update(
                    'perdidas',
                    $perdidasData,
                    'paciente_id = :paciente_id AND hora = :hora',
                    [':paciente_id' => $pacienteId, ':hora' => $hora]
                );
            } else {
                $db->insert('perdidas', $perdidasData);
            }
            
            $sincronizados++;
            
        } catch(Exception $e) {
            $errores[] = "Hora $hora: " . $e->getMessage();
        }
    }
    
    // Recalcular balances
    recalcularBalancesSync($db, $pacienteId);
    
    return [
        'seccion' => 'perdidas',
        'registros_procesados' => $sincronizados,
        'errores' => $errores,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// Funciones de validación reutilizadas
function validarRangoConstantes($valor, $min, $max) {
    if($valor === null || $valor === '') {
        return null;
    }
    
    $valor = (float)$valor;
    
    if($valor < $min || $valor > $max) {
        throw new Exception("Valor $valor fuera del rango permitido ($min-$max)");
    }
    
    return $valor;
}

function validarRangoOxigenacion($valor, $min, $max, $allowNull = false) {
    if($valor === null || $valor === '') {
        return $allowNull ? null : throw new Exception("Valor requerido");
    }
    
    if(!is_numeric($valor)) {
        return $allowNull ? null : throw new Exception("Valor debe ser numérico");
    }
    
    $valor = (float)$valor;
    
    if($valor < $min || $valor > $max) {
        throw new Exception("Valor $valor fuera del rango permitido ($min-$max)");
    }
    
    return $valor;
}

function validarTipoOxigeno($tipo) {
    $tiposValidos = ['', 'VMI', 'VMNI', 'O2'];
    return in_array($tipo, $tiposValidos) ? $tipo : '';
}

function validarTipoInsulina($tipo) {
    $tiposValidos = ['S/P', 'PERFUSIÓN'];
    return in_array($tipo, $tiposValidos) ? $tipo : 'S/P';
}

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

function calcularFiebreTqnSync($db, $pacienteId, $hora, $peso) {
    if($peso <= 0) return 0;
    
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
    
    // Cálculos de TQN
    if($frecuenciaResp > 35) {
        $total += 0.3 * $peso;
    } elseif($frecuenciaResp > 25) {
        $total += 0.2 * $peso;
    }
    
    return round($total, 1);
}

function recalcularBalancesSync($db, $pacienteId) {
    // Implementación similar a la función en perdidas.php
    $paciente = $db->getById('pacientes', $pacienteId);
    if(!$paciente) return;
    
    $peso = (float)($paciente['peso'] ?? 0);
    $horasIngreso = calcularHorasIngresoSync($paciente['fecha_ingreso']);
    
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
    $perdidasInsensibles = round(0.5 * $peso * $horasIngreso, 1);
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
    
    // Upsert balances
    $existeBalance = $db->exists('balances_diarios', 'paciente_id = :id', [':id' => $pacienteId]);
    
    if($existeBalance) {
        unset($balances['paciente_id']);
        $db->update('balances_diarios', $balances, 'paciente_id = :id', [':id' => $pacienteId]);
    } else {
        $db->insert('balances_diarios', $balances);
    }
    
    return $balances;
}

function calcularHorasIngresoSync($fechaIngreso) {
    if(!$fechaIngreso) return 24;
    
    $ingreso = new DateTime($fechaIngreso);
    $ahora = new DateTime();
    
    if($ahora->format('H') < 8) {
        $ahora->modify('-1 day');
    }
    
    if($ingreso->format('H') < 8) {
        $ingreso->modify('-1 day');
    }
    
    $diff = $ahora->diff($ingreso);
    return min(24, max(0, $diff->h + ($diff->days * 24)));
}

/**
 * Endpoints adicionales
 */

// Endpoint para verificar estado de sincronización
if(isset($_GET['check_sync_status'])) {
    $pacienteId = $_GET['paciente_id'] ?? null;
    
    if(!$pacienteId) {
        ApiResponse::json(ApiResponse::error("ID de paciente requerido", 400));
    }
    
    $status = [
        'paciente_id' => $pacienteId,
        'existe' => $db->exists('pacientes', 'id = :id', [':id' => $pacienteId]),
        'constantes_vitales' => $db->getAll('constantes_vitales', 'paciente_id = :id', [':id' => $pacienteId], '', '1'),
        'oxigenacion_dolor' => $db->getAll('oxigenacion_dolor', 'paciente_id = :id', [':id' => $pacienteId], '', '1'),
        'perdidas' => $db->getAll('perdidas', 'paciente_id = :id', [':id' => $pacienteId], '', '1'),
        'balances' => $db->getAll('balances_diarios', 'paciente_id = :id', [':id' => $pacienteId], '', '1'),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $status['has_data'] = [
        'constantes' => !empty($status['constantes_vitales']),
        'oxigenacion' => !empty($status['oxigenacion_dolor']),
        'perdidas' => !empty($status['perdidas']),
        'balances' => !empty($status['balances'])
    ];
    
    ApiResponse::json(ApiResponse::success($status, "Estado de sincronización obtenido"));
}

// Endpoint para test de conectividad
if(isset($_GET['test_connection'])) {
    $testResults = [
        'database_connection' => $db->testConnection(),
        'database_info' => $db->getDbInfo(),
        'timestamp' => date('Y-m-d H:i:s'),
        'server_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];
    
    ApiResponse::json(ApiResponse::success($testResults, "Test de conexión completado"));
}
?>