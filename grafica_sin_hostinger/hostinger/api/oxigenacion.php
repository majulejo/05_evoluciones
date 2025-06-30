<?php
/**
 * API para gestión de oxigenación, dolor y glucemias (Sección 2)
 * Endpoints: GET, POST, PUT, DELETE /api/oxigenacion.php
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
    logAction("ERROR_OXIGENACION", $e->getMessage());
    ApiResponse::json(ApiResponse::error($e->getMessage()));
}

/**
 * GET - Obtener datos de oxigenación y dolor
 */
function handleGet($db) {
    $pacienteId = $_GET['paciente_id'] ?? null;
    $hora = $_GET['hora'] ?? null;
    $formato = $_GET['formato'] ?? 'array';
    
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
            'oxigenacion_dolor',
            'paciente_id = :paciente_id AND hora = :hora',
            [':paciente_id' => $pacienteId, ':hora' => $hora]
        );
        
        $resultado = !empty($datos) ? formatearDatosHora($datos[0]) : null;
        ApiResponse::json(ApiResponse::success($resultado, "Datos obtenidos"));
        return;
    }
    
    // Obtener todos los datos del paciente
    $datos = $db->getAll(
        'oxigenacion_dolor',
        'paciente_id = :paciente_id',
        [':paciente_id' => $pacienteId],
        'hora ASC'
    );
    
    if($formato === 'array') {
        // Formato compatible con JavaScript section2Data
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
        
        ApiResponse::json(ApiResponse::success($section2Data, "Datos obtenidos en formato array"));
    } else {
        // Formato objeto con metadatos
        $resultado = [
            'paciente_id' => $pacienteId,
            'total_registros' => count($datos),
            'horas_con_datos' => array_column($datos, 'hora'),
            'datos' => array_map('formatearDatosHora', $datos)
        ];
        
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
    
    // Preparar datos para inserción/actualización
    $oxigenacionData = [
        'paciente_id' => $pacienteId,
        'hora' => $hora,
        'neumotorax_porcentaje' => validarRango($data['pneumo'] ?? null, 0, 100, true),
        'tipo_oxigeno' => validarTipoOxigeno($data['oxygen'] ?? ''),
        'saturacion_manual' => validarRango($data['saturation'] ?? null, 0, 100),
        'eva_dolor' => validarRango($data['eva'] ?? null, 0, 10, true),
        'rass_sedacion' => validarRango($data['rass'] ?? null, -5, 4, true),
        'glucemia_manual' => validarRango($data['glucose'] ?? null, 0, 600, true),
        'insulina_valor' => substr(($data['insulina_valor'] ?? ''), 0, 50),
        'insulina_tipo' => validarTipoInsulina($data['insulina_tipo'] ?? 'S/P'),
        'insulina_recomendada' => substr(($data['insulina_recomendada'] ?? ''), 0, 50),
        'insulina_mensaje' => substr(($data['insulina_mensaje'] ?? ''), 0, 255)
    ];
    
    // Verificar si ya existe registro para esta hora
    $existeRegistro = $db->exists(
        'oxigenacion_dolor',
        'paciente_id = :paciente_id AND hora = :hora',
        [':paciente_id' => $pacienteId, ':hora' => $hora]
    );
    
    if($existeRegistro) {
        // Actualizar registro existente
        $db->update(
            'oxigenacion_dolor',
            $oxigenacionData,
            'paciente_id = :paciente_id AND hora = :hora',
            [':paciente_id' => $pacienteId, ':hora' => $hora]
        );
        
        $mensaje = "Datos actualizados correctamente";
    } else {
        // Crear nuevo registro
        $db->insert('oxigenacion_dolor', $oxigenacionData);
        $mensaje = "Datos creados correctamente";
    }
    
    // Obtener el registro actualizado/creado
    $datosActualizados = $db->getAll(
        'oxigenacion_dolor',
        'paciente_id = :paciente_id AND hora = :hora',
        [':paciente_id' => $pacienteId, ':hora' => $hora]
    );
    
    logAction("GUARDAR_OXIGENACION", [
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
    validateRequired($data, ['paciente_id', 'section2Data']);
    
    $pacienteId = $data['paciente_id'];
    $section2Data = $data['section2Data'];
    
    // Verificar que el paciente existe
    if(!$db->exists('pacientes', 'id = :id', [':id' => $pacienteId])) {
        ApiResponse::json(ApiResponse::error("Paciente no encontrado", 404));
    }
    
    $operaciones = [];
    $resultados = [];
    
    // Procesar cada hora
    for($hora = 0; $hora < 24; $hora++) {
        // Verificar si hay datos para esta hora
        $tieneValores = false;
        $valores = [
            'pneumo' => $section2Data['pneumo'][$hora] ?? '',
            'oxygen' => $section2Data['oxygen'][$hora] ?? '',
            'saturation' => $section2Data['saturation'][$hora] ?? '',
            'eva' => $section2Data['eva'][$hora]['eva'] ?? '',
            'rass' => $section2Data['eva'][$hora]['rass'] ?? '',
            'glucose' => $section2Data['glucose'][$hora] ?? '',
            'insulina_valor' => $section2Data['insulin'][$hora]['value'] ?? '',
            'insulina_tipo' => $section2Data['insulin'][$hora]['type'] ?? 'S/P',
            'insulina_recomendada' => $section2Data['insulin'][$hora]['recommended'] ?? '',
            'insulina_mensaje' => $section2Data['insulin'][$hora]['message'] ?? ''
        ];
        
        foreach($valores as $valor) {
            if($valor !== null && $valor !== '') {
                $tieneValores = true;
                break;
            }
        }
        
        if(!$tieneValores) {
            continue;
        }
        
        try {
            $oxigenacionData = [
                'paciente_id' => $pacienteId,
                'hora' => $hora,
                'neumotorax_porcentaje' => validarRango($valores['pneumo'], 0, 100, true),
                'tipo_oxigeno' => validarTipoOxigeno($valores['oxygen']),
                'saturacion_manual' => validarRango($valores['saturation'], 0, 100),
                'eva_dolor' => validarRango($valores['eva'], 0, 10, true),
                'rass_sedacion' => validarRango($valores['rass'], -5, 4, true),
                'glucemia_manual' => validarRango($valores['glucose'], 0, 600, true),
                'insulina_valor' => substr($valores['insulina_valor'], 0, 50),
                'insulina_tipo' => validarTipoInsulina($valores['insulina_tipo']),
                'insulina_recomendada' => substr($valores['insulina_recomendada'], 0, 50),
                'insulina_mensaje' => substr($valores['insulina_mensaje'], 0, 255)
            ];
            
            // Verificar si existe el registro
            $existe = $db->exists(
                'oxigenacion_dolor',
                'paciente_id = :paciente_id AND hora = :hora',
                [':paciente_id' => $pacienteId, ':hora' => $hora]
            );
            
            if($existe) {
                $operaciones[] = [
                    'method' => 'update',
                    'params' => [
                        'oxigenacion_dolor',
                        $oxigenacionData,
                        'paciente_id = :paciente_id AND hora = :hora',
                        [':paciente_id' => $pacienteId, ':hora' => $hora]
                    ]
                ];
            } else {
                $operaciones[] = [
                    'method' => 'insert',
                    'params' => ['oxigenacion_dolor', $oxigenacionData]
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
        
        logAction("GUARDAR_MULTIPLE_OXIGENACION", [
            'paciente_id' => $pacienteId,
            'operaciones' => count($operaciones)
        ]);
        
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
    if(!$db->exists('oxigenacion_dolor', 'paciente_id = :paciente_id AND hora = :hora', 
                   [':paciente_id' => $pacienteId, ':hora' => $hora])) {
        ApiResponse::json(ApiResponse::error("Registro no encontrado", 404));
    }
    
    // Eliminar registro
    $db->delete(
        'oxigenacion_dolor',
        'paciente_id = :paciente_id AND hora = :hora',
        [':paciente_id' => $pacienteId, ':hora' => $hora]
    );
    
    logAction("ELIMINAR_OXIGENACION", [
        'paciente_id' => $pacienteId,
        'hora' => $hora
    ]);
    
    ApiResponse::json(ApiResponse::success(null, "Datos eliminados correctamente"));
}

/**
 * Funciones de validación específicas
 */
function validarRango($valor, $min, $max, $allowNull = false) {
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
    
    if(!in_array($tipo, $tiposValidos)) {
        return '';
    }
    
    return $tipo;
}

function validarTipoInsulina($tipo) {
    $tiposValidos = ['S/P', 'PERFUSIÓN'];
    
    if(!in_array($tipo, $tiposValidos)) {
        return 'S/P';
    }
    
    return $tipo;
}

function formatearDatosHora($dato) {
    return [
        'id' => $dato['id'],
        'paciente_id' => $dato['paciente_id'],
        'hora' => (int)$dato['hora'],
        'pneumo' => $dato['neumotorax_porcentaje'],
        'oxygen' => $dato['tipo_oxigeno'],
        'saturation' => $dato['saturacion_manual'],
        'eva' => $dato['eva_dolor'],
        'rass' => $dato['rass_sedacion'],
        'glucose' => $dato['glucemia_manual'],
        'insulina_valor' => $dato['insulina_valor'],
        'insulina_tipo' => $dato['insulina_tipo'],
        'insulina_recomendada' => $dato['insulina_recomendada'],
        'insulina_mensaje' => $dato['insulina_mensaje'],
        'fecha_registro' => $dato['fecha_registro']
    ];
}

/**
 * Endpoint para calcular dosis de insulina recomendada
 */
if(isset($_GET['calcular_insulina'])) {
    $glucemia = $_GET['glucemia'] ?? null;
    
    if(!$glucemia || !is_numeric($glucemia)) {
        ApiResponse::json(ApiResponse::error("Glucemia requerida y debe ser numérica", 400));
    }
    
    $glucemia = (float)$glucemia;
    $dosis = calcularDosisInsulina($glucemia);
    
    ApiResponse::json(ApiResponse::success([
        'glucemia' => $glucemia,
        'dosis_recomendada' => $dosis['dosis'],
        'mensaje' => $dosis['mensaje'],
        'es_critica' => $dosis['critica']
    ], "Dosis calculada"));
}

/**
 * Calcular dosis de insulina según protocolo
 */
function calcularDosisInsulina($glucemia) {
    if($glucemia < 150) {
        return [
            'dosis' => 'NADA',
            'mensaje' => 'Glucemia normal',
            'critica' => false
        ];
    }
    
    if($glucemia >= 151 && $glucemia <= 225) {
        return [
            'dosis' => '6U s/c',
            'mensaje' => 'Administrar 6 U.I. subcutánea',
            'critica' => false
        ];
    }
    
    if($glucemia >= 226 && $glucemia <= 250) {
        return [
            'dosis' => '10U s/c',
            'mensaje' => 'Administrar 10 U.I. subcutánea',
            'critica' => false
        ];
    }
    
    if($glucemia >= 251 && $glucemia <= 300) {
        return [
            'dosis' => '15U s/c',
            'mensaje' => 'Administrar 15 U.I. subcutánea',
            'critica' => false
        ];
    }
    
    if($glucemia >= 301 && $glucemia <= 350) {
        return [
            'dosis' => '20U s/c',
            'mensaje' => 'Administrar 20 U.I. subcutánea',
            'critica' => false
        ];
    }
    
    if($glucemia >= 351 && $glucemia <= 400) {
        return [
            'dosis' => '20U s/c + 5U I.V.',
            'mensaje' => 'Administrar 20 U.I. subcutánea + 5 U.I. intravenosa. AVISAR AL FACULTATIVO',
            'critica' => true
        ];
    }
    
    // > 400
    return [
        'dosis' => 'AVISAR AL FACULTATIVO',
        'mensaje' => 'Glucemia muy elevada. AVISAR INMEDIATAMENTE AL FACULTATIVO',
        'critica' => true
    ];
}

/**
 * Endpoint para sincronizar section2Data desde localStorage
 */
if(isset($_GET['sync_section2'])) {
    $method = validateMethod(['POST']);
    $data = getJsonInput();
    
    validateRequired($data, ['paciente_id', 'section2Data']);
    
    $pacienteId = $data['paciente_id'];
    $section2Data = $data['section2Data'];
    
    // Validar estructura de section2Data
    $camposRequeridos = ['pneumo', 'oxygen', 'saturation', 'eva', 'glucose', 'insulin'];
    foreach($camposRequeridos as $campo) {
        if(!isset($section2Data[$campo]) || !is_array($section2Data[$campo])) {
            ApiResponse::json(ApiResponse::error("Campo $campo faltante o inválido en section2Data", 400));
        }
    }
    
    $sincronizados = 0;
    $errores = [];
    
    for($hora = 0; $hora < 24; $hora++) {
        try {
            // Extraer datos de esta hora
            $pneumo = $section2Data['pneumo'][$hora] ?? '';
            $oxygen = $section2Data['oxygen'][$hora] ?? '';
            $saturation = $section2Data['saturation'][$hora] ?? '';
            $eva = $section2Data['eva'][$hora]['eva'] ?? '';
            $rass = $section2Data['eva'][$hora]['rass'] ?? '';
            $glucose = $section2Data['glucose'][$hora] ?? '';
            $insulin = $section2Data['insulin'][$hora];
            
            // Verificar si hay algún dato válido para esta hora
            $tieneValores = false;
            $valores = [$pneumo, $oxygen, $saturation, $eva, $rass, $glucose, 
                       $insulin['value'] ?? '', $insulin['type'] ?? ''];
            
            foreach($valores as $valor) {
                if($valor !== null && $valor !== '' && $valor !== 'S/P') {
                    $tieneValores = true;
                    break;
                }
            }
            
            if(!$tieneValores) {
                continue; // Saltar esta hora si no hay datos
            }
            
            $oxigenacionData = [
                'paciente_id' => $pacienteId,
                'hora' => $hora,
                'neumotorax_porcentaje' => validarRango($pneumo, 0, 100, true),
                'tipo_oxigeno' => validarTipoOxigeno($oxygen),
                'saturacion_manual' => validarRango($saturation, 0, 100, true),
                'eva_dolor' => validarRango($eva, 0, 10, true),
                'rass_sedacion' => validarRango($rass, -5, 4, true),
                'glucemia_manual' => validarRango($glucose, 0, 600, true),
                'insulina_valor' => substr(($insulin['value'] ?? ''), 0, 50),
                'insulina_tipo' => validarTipoInsulina($insulin['type'] ?? 'S/P'),
                'insulina_recomendada' => substr(($insulin['recommended'] ?? ''), 0, 50),
                'insulina_mensaje' => substr(($insulin['message'] ?? ''), 0, 255)
            ];
            
            // Upsert (insert or update)
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
    
    logAction("SYNC_SECTION2", [
        'paciente_id' => $pacienteId,
        'sincronizados' => $sincronizados,
        'errores' => count($errores)
    ]);
    
    $resultado = [
        'sincronizados' => $sincronizados,
        'errores' => $errores,
        'total_procesados' => 24
    ];
    
    if($sincronizados > 0) {
        ApiResponse::json(ApiResponse::success($resultado, "Sección 2 sincronizada correctamente"));
    } else {
        ApiResponse::json(ApiResponse::error("No se pudo sincronizar ningún registro de la sección 2", 400, $resultado));
    }
}

/**
 * Endpoint para obtener estadísticas de la sección 2
 */
if(isset($_GET['estadisticas_seccion2'])) {
    $pacienteId = $_GET['paciente_id'] ?? null;
    
    if(!$pacienteId) {
        ApiResponse::json(ApiResponse::error("ID de paciente requerido", 400));
    }
    
    $query = "
        SELECT 
            COUNT(*) as total_registros,
            COUNT(DISTINCT hora) as horas_registradas,
            AVG(saturacion_manual) as saturacion_promedio,
            AVG(glucemia_manual) as glucemia_promedio,
            AVG(eva_dolor) as eva_promedio,
            AVG(rass_sedacion) as rass_promedio,
            MIN(glucemia_manual) as glucemia_min,
            MAX(glucemia_manual) as glucemia_max,
            MIN(saturacion_manual) as saturacion_min,
            MAX(saturacion_manual) as saturacion_max,
            COUNT(CASE WHEN tipo_oxigeno = 'VMI' THEN 1 END) as horas_vmi,
            COUNT(CASE WHEN tipo_oxigeno = 'VMNI' THEN 1 END) as horas_vmni,
            COUNT(CASE WHEN tipo_oxigeno = 'O2' THEN 1 END) as horas_o2,
            COUNT(CASE WHEN glucemia_manual > 350 THEN 1 END) as glucemias_criticas,
            COUNT(CASE WHEN eva_dolor > 6 THEN 1 END) as dolor_severo
        FROM oxigenacion_dolor 
        WHERE paciente_id = :paciente_id
    ";
    
    $stmt = $db->executeQuery($query, [':paciente_id' => $pacienteId]);
    $estadisticas = $stmt->fetch();
    
    // Formatear números
    foreach($estadisticas as $key => $value) {
        if(strpos($key, 'promedio') !== false || strpos($key, 'min') !== false || strpos($key, 'max') !== false) {
            $estadisticas[$key] = $value ? round($value, 1) : null;
        }
    }
    
    // Agregar porcentajes
    $total = $estadisticas['horas_registradas'];
    if($total > 0) {
        $estadisticas['porcentaje_vmi'] = round(($estadisticas['horas_vmi'] / $total) * 100, 1);
        $estadisticas['porcentaje_vmni'] = round(($estadisticas['horas_vmni'] / $total) * 100, 1);
        $estadisticas['porcentaje_o2'] = round(($estadisticas['horas_o2'] / $total) * 100, 1);
    }
    
    ApiResponse::json(ApiResponse::success($estadisticas, "Estadísticas de sección 2 obtenidas"));
}
?>