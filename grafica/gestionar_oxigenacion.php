<?php
// gestionar_oxigenacion.php - REEMPLAZAR ARCHIVO COMPLETO
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $accion = $_POST['accion'] ?? null;
    $box = $_POST['box'] ?? null;
    $hora = $_POST['hora'] ?? null;
    
    if (!$accion || !$box || !$hora) {
        throw new Exception("Parámetros requeridos faltantes");
    }
    
    // Conectar a la base de datos
    $conexion = new mysqli("localhost", "u724879249_data", "Farolill0.1", "u724879249_data");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8");
    
    if ($accion === 'guardar_oxigenacion') {
        // Verificar si ya existe un registro para esta hora
        $stmt_check = $conexion->prepare("SELECT id FROM oxigenacion_datos WHERE box = ? AND hora = ?");
        $stmt_check->bind_param("ss", $box, $hora);
        $stmt_check->execute();
        $existe = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        
        // Obtener datos del formulario (cualquier campo puede venir)
        $campos_permitidos = ['pNeumo', 'tipoOxigenacion', 'evaEscid', 'rass', 'insulina'];
        $datos_actualizar = [];
        $tipos_datos = "";
        $valores = [];
        
        foreach ($campos_permitidos as $campo) {
            if (isset($_POST[$campo])) {
                $valor = $_POST[$campo];
                if ($valor !== '' && $valor !== null) {
                    $campo_db = strtolower(preg_replace('/([A-Z])/', '_$1', $campo));
                    if ($campo === 'pNeumo') $campo_db = 'p_neumo';
                    if ($campo === 'tipoOxigenacion') $campo_db = 'tipo_oxigenacion';
                    if ($campo === 'evaEscid') $campo_db = 'eva_escid';
                    
                    $datos_actualizar[$campo_db] = $valor;
                    
                    if (in_array($campo, ['pNeumo', 'evaEscid', 'rass'])) {
                        $tipos_datos .= "i";
                        $valores[] = (int)$valor;
                    } elseif ($campo === 'insulina') {
                        $tipos_datos .= "d";
                        $valores[] = (float)$valor;
                    } else {
                        $tipos_datos .= "s";
                        $valores[] = $valor;
                    }
                }
            }
        }
        
        if (empty($datos_actualizar)) {
            throw new Exception("No hay datos para actualizar");
        }
        
        if ($existe) {
            // Actualizar registro existente
            $campos_set = array_map(function($campo) { return "$campo = ?"; }, array_keys($datos_actualizar));
            $sql = "UPDATE oxigenacion_datos SET " . implode(", ", $campos_set) . ", fecha_registro = NOW() WHERE box = ? AND hora = ?";
            $tipos_datos .= "ss";
            $valores[] = $box;
            $valores[] = $hora;
        } else {
            // Crear nuevo registro
            $campos = implode(", ", array_keys($datos_actualizar));
            $placeholders = str_repeat("?,", count($datos_actualizar) - 1) . "?";
            $sql = "INSERT INTO oxigenacion_datos (box, hora, $campos, fecha_registro) VALUES (?, ?, $placeholders, NOW())";
            $tipos_datos = "ss" . $tipos_datos;
            array_unshift($valores, $box, $hora);
        }
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($tipos_datos, ...$valores);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar datos: " . $stmt->error);
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Datos guardados correctamente'
        ]);
        
    } elseif ($accion === 'eliminar_oxigenacion') {
        // Eliminar registro
        $stmt = $conexion->prepare("DELETE FROM oxigenacion_datos WHERE box = ? AND hora = ?");
        $stmt->bind_param("ss", $box, $hora);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar datos: " . $stmt->error);
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Datos eliminados correctamente'
        ]);
        
    } else {
        throw new Exception("Acción no válida");
    }
    
    $conexion->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>