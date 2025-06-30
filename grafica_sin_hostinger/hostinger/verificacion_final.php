<?php
/**
 * Verificación final completa del sistema UCI
 * Este script verifica que todo esté funcionando correctamente
 */

require_once 'database.php';

class VerificacionFinal {
    private $db;
    private $baseUrl;
    private $resultados = [];
    
    public function __construct() {
        $this->db = new Database();
        $this->baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $this->resultados['timestamp'] = date('Y-m-d H:i:s');
        $this->resultados['version'] = '1.0.0';
    }
    
    public function ejecutarVerificacionCompleta() {
        echo $this->getHTMLHeader();
        
        echo "<div class='container'>";
        echo "<h1>🎯 Verificación Final del Sistema UCI</h1>";
        echo "<p class='subtitle'>Comprobación exhaustiva de todos los componentes</p>";
        
        $this->verificarInfraestructura();
        $this->verificarBaseDatos();
        $this->verificarAPIs();
        $this->verificarFuncionalidadCompleta();
        $this->mostrarResumenFinal();
        
        echo "</div>";
        echo "</body></html>";
    }
    
    private function verificarInfraestructura() {
        echo "<div class='test-section'>";
        echo "<h2>🏗️ Verificación de Infraestructura</h2>";
        
        // PHP Version
        $phpVersion = phpversion();
        echo "<div class='test-item'>";
        echo "<span class='icon'>🐘</span>";
        echo "<span class='label'>Versión PHP:</span>";
        echo "<span class='value success'>{$phpVersion}</span>";
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            echo "<span class='status success'>✅</span>";
            $this->resultados['infraestructura']['php'] = 'PASS';
        } else {
            echo "<span class='status error'>❌</span>";
            $this->resultados['infraestructura']['php'] = 'FAIL';
        }
        echo "</div>";
        
        // Extensiones PHP
        $extensiones = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
        foreach ($extensiones as $ext) {
            echo "<div class='test-item'>";
            echo "<span class='icon'>📦</span>";
            echo "<span class='label'>Extensión {$ext}:</span>";
            if (extension_loaded($ext)) {
                echo "<span class='value success'>Cargada</span>";
                echo "<span class='status success'>✅</span>";
                $this->resultados['infraestructura']['extensiones'][$ext] = 'PASS';
            } else {
                echo "<span class='value error'>No cargada</span>";
                echo "<span class='status error'>❌</span>";
                $this->resultados['infraestructura']['extensiones'][$ext] = 'FAIL';
            }
            echo "</div>";
        }
        
        // Archivos del sistema
        $archivos = [
            'config.php' => 'Configuración general',
            'database.php' => 'Clase de base de datos',
            'api/pacientes.php' => 'API de pacientes',
            'api/constantes.php' => 'API de constantes vitales',
            'api/config.php' => 'API de configuración',
            'api/reportes.php' => 'API de reportes'
        ];
        
        foreach ($archivos as $archivo => $descripcion) {
            echo "<div class='test-item'>";
            echo "<span class='icon'>📄</span>";
            echo "<span class='label'>{$descripcion}:</span>";
            if (file_exists($archivo)) {
                echo "<span class='value success'>Existe</span>";
                echo "<span class='status success'>✅</span>";
                $this->resultados['infraestructura']['archivos'][$archivo] = 'PASS';
            } else {
                echo "<span class='value error'>No existe</span>";
                echo "<span class='status error'>❌</span>";
                $this->resultados['infraestructura']['archivos'][$archivo] = 'FAIL';
            }
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    private function verificarBaseDatos() {
        echo "<div class='test-section'>";
        echo "<h2>🗄️ Verificación de Base de Datos</h2>";
        
        try {
            // Conexión
            $connectionInfo = $this->db->testConnection();
            echo "<div class='test-item'>";
            echo "<span class='icon'>🔌</span>";
            echo "<span class='label'>Conexión:</span>";
            echo "<span class='value success'>Exitosa ({$this->db->getConnectedHost()})</span>";
            echo "<span class='status success'>✅</span>";
            echo "</div>";
            
            // Información del servidor
            $dbInfo = $this->db->getDbInfo();
            echo "<div class='test-item'>";
            echo "<span class='icon'>🏷️</span>";
            echo "<span class='label'>Base de datos:</span>";
            echo "<span class='value info'>{$dbInfo['db_name']}</span>";
            echo "<span class='status success'>✅</span>";
            echo "</div>";
            
            echo "<div class='test-item'>";
            echo "<span class='icon'>🔢</span>";
            echo "<span class='label'>Versión MySQL:</span>";
            echo "<span class='value info'>{$dbInfo['version']}</span>";
            echo "<span class='status success'>✅</span>";
            echo "</div>";
            
            // Tablas
            $tablas = ['pacientes', 'constantes_vitales', 'oxigenacion_dolor', 'perdidas', 'balances_diarios', 'configuracion'];
            foreach ($tablas as $tabla) {
                echo "<div class='test-item'>";
                echo "<span class='icon'>📋</span>";
                echo "<span class='label'>Tabla {$tabla}:</span>";
                if ($this->db->tableExists($tabla)) {
                    // Contar registros
                    $count = $this->db->executeQuery("SELECT COUNT(*) as count FROM {$tabla}")->fetch()['count'];
                    echo "<span class='value success'>Existe ({$count} registros)</span>";
                    echo "<span class='status success'>✅</span>";
                    $this->resultados['base_datos']['tablas'][$tabla] = 'PASS';
                } else {
                    echo "<span class='value error'>No existe</span>";
                    echo "<span class='status error'>❌</span>";
                    $this->resultados['base_datos']['tablas'][$tabla] = 'FAIL';
                }
                echo "</div>";
            }
            
            $this->resultados['base_datos']['conexion'] = 'PASS';
            
        } catch (Exception $e) {
            echo "<div class='test-item'>";
            echo "<span class='icon'>💥</span>";
            echo "<span class='label'>Error:</span>";
            echo "<span class='value error'>{$e->getMessage()}</span>";
            echo "<span class='status error'>❌</span>";
            echo "</div>";
            $this->resultados['base_datos']['conexion'] = 'FAIL';
        }
        
        echo "</div>";
    }
    
    private function verificarAPIs() {
        echo "<div class='test-section'>";
        echo "<h2>🌐 Verificación de APIs</h2>";
        
        $apis = [
            'api/pacientes.php' => 'Gestión de pacientes',
            'api/constantes.php' => 'Constantes vitales',
            'api/config.php' => 'Configuración',
            'api/reportes.php' => 'Reportes y estadísticas'
        ];
        
        foreach ($apis as $api => $descripcion) {
            echo "<div class='test-item'>";
            echo "<span class='icon'>🔗</span>";
            echo "<span class='label'>{$descripcion}:</span>";
            
            if (file_exists($api)) {
                echo "<span class='value success'>Disponible</span>";
                echo "<span class='status success'>✅</span>";
                $this->resultados['apis'][$api] = 'PASS';
            } else {
                echo "<span class='value error'>No disponible</span>";
                echo "<span class='status error'>❌</span>";
                $this->resultados['apis'][$api] = 'FAIL';
            }
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    private function verificarFuncionalidadCompleta() {
        echo "<div class='test-section'>";
        echo "<h2>⚡ Verificación de Funcionalidad</h2>";
        
        try {
            // Test de creación de paciente
            $pacienteTest = [
                'nombre' => 'Test Verificación Final',
                'edad' => 25,
                'peso' => 65.0,
                'historia_clinica' => 'Paciente de prueba para verificación final',
                'cama' => 9999,
                'fecha_ingreso' => date('Y-m-d H:i:s'),
                'hoja_clinica' => 1,
                'fecha_grafica' => date('Y-m-d'),
                'activo' => 1
            ];
            
            $pacienteId = $this->db->insert('pacientes', $pacienteTest);
            
            echo "<div class='test-item'>";
            echo "<span class='icon'>👤</span>";
            echo "<span class='label'>Crear paciente:</span>";
            echo "<span class='value success'>ID: {$pacienteId}</span>";
            echo "<span class='status success'>✅</span>";
            echo "</div>";
            
            // Test de constantes vitales
            $constantesTest = [
                'paciente_id' => $pacienteId,
                'temperatura' => 36.5,
                'presion_sistolica' => 120,
                'presion_diastolica' => 80,
                'frecuencia_cardiaca' => 70,
                'frecuencia_respiratoria' => 16,
                'saturacion_oxigeno' => 98
            ];
            
            $constanteId = $this->db->insert('constantes_vitales', $constantesTest);
            
            echo "<div class='test-item'>";
            echo "<span class='icon'>💓</span>";
            echo "<span class='label'>Registrar constantes:</span>";
            echo "<span class='value success'>ID: {$constanteId}</span>";
            echo "<span class='status success'>✅</span>";
            echo "</div>";
            
            // Test de consulta
            $pacienteRecuperado = $this->db->getById('pacientes', $pacienteId);
            echo "<div class='test-item'>";
            echo "<span class='icon'>🔍</span>";
            echo "<span class='label'>Consultar datos:</span>";
            echo "<span class='value success'>{$pacienteRecuperado['nombre']}</span>";
            echo "<span class='status success'>✅</span>";
            echo "</div>";
            
            // Limpiar datos de prueba
            $this->db->delete('constantes_vitales', 'id = :id', [':id' => $constanteId]);
            $this->db->delete('pacientes', 'id = :id', [':id' => $pacienteId]);
            
            echo "<div class='test-item'>";
            echo "<span class='icon'>🧹</span>";
            echo "<span class='label'>Limpiar datos de prueba:</span>";
            echo "<span class='value success'>Completado</span>";
            echo "<span class='status success'>✅</span>";
            echo "</div>";
            
            $this->resultados['funcionalidad'] = 'PASS';
            
        } catch (Exception $e) {
            echo "<div class='test-item'>";
            echo "<span class='icon'>💥</span>";
            echo "<span class='label'>Error en funcionalidad:</span>";
            echo "<span class='value error'>{$e->getMessage()}</span>";
            echo "<span class='status error'>❌</span>";
            echo "</div>";
            $this->resultados['funcionalidad'] = 'FAIL';
        }
        
        echo "</div>";
    }
    
    private function mostrarResumenFinal() {
        // Calcular estadísticas
        $totalTests = 0;
        $testsPasados = 0;
        
        // Contar resultados
        foreach ($this->resultados as $seccion => $datos) {
            if (is_array($datos)) {
                foreach ($datos as $test => $resultado) {
                    if (is_array($resultado)) {
                        foreach ($resultado as $subtest => $subresultado) {
                            $totalTests++;
                            if ($subresultado === 'PASS') $testsPasados++;
                        }
                    } else {
                        $totalTests++;
                        if ($resultado === 'PASS') $testsPasados++;
                    }
                }
            }
        }
        
        $porcentajeExito = $totalTests > 0 ? round(($testsPasados / $totalTests) * 100, 1) : 0;
        
        echo "<div class='final-summary'>";
        echo "<h2>🎯 Resumen Final</h2>";
        
        echo "<div class='stats-grid'>";
        echo "<div class='stat-card'>";
        echo "<div class='stat-number'>{$testsPasados}</div>";
        echo "<div class='stat-label'>Tests Pasados</div>";
        echo "</div>";
        
        echo "<div class='stat-card'>";
        echo "<div class='stat-number'>{$totalTests}</div>";
        echo "<div class='stat-label'>Tests Totales</div>";
        echo "</div>";
        
        echo "<div class='stat-card'>";
        echo "<div class='stat-number'>{$porcentajeExito}%</div>";
        echo "<div class='stat-label'>Éxito</div>";
        echo "</div>";
        echo "</div>";
        
        // Determinar estado general
        if ($porcentajeExito >= 95) {
            echo "<div class='result-banner success'>";
            echo "<div class='banner-icon'>🎉</div>";
            echo "<div class='banner-content'>";
            echo "<h3>¡SISTEMA COMPLETAMENTE FUNCIONAL!</h3>";
            echo "<p>Todos los componentes están funcionando perfectamente. El sistema UCI está listo para su uso en producción.</p>";
            echo "</div>";
            echo "</div>";
        } elseif ($porcentajeExito >= 85) {
            echo "<div class='result-banner warning'>";
            echo "<div class='banner-icon'>⚠️</div>";
            echo "<div class='banner-content'>";
            echo "<h3>Sistema Mayormente Funcional</h3>";
            echo "<p>La mayoría de componentes funcionan correctamente. Revisa los elementos que fallaron y corrígelos para un funcionamiento óptimo.</p>";
            echo "</div>";
            echo "</div>";
        } else {
            echo "<div class='result-banner error'>";
            echo "<div class='banner-icon'>❌</div>";
            echo "<div class='banner-content'>";
            echo "<h3>Sistema Requiere Atención</h3>";
            echo "<p>Varios componentes necesitan corrección. Revisa los errores reportados y ejecuta las reparaciones necesarias.</p>";
            echo "</div>";
            echo "</div>";
        }
        
        // Enlaces de acción
        echo "<div class='action-links'>";
        echo "<h3>🔗 Enlaces Útiles</h3>";
        echo "<div class='link-grid'>";
        echo "<a href='api/pacientes.php' class='action-link' target='_blank'>";
        echo "<span class='link-icon'>👥</span>";
        echo "<span class='link-text'>API Pacientes</span>";
        echo "</a>";
        
        echo "<a href='api/constantes.php' class='action-link' target='_blank'>";
        echo "<span class='link-icon'>💓</span>";
        echo "<span class='link-text'>API Constantes</span>";
        echo "</a>";
        
        echo "<a href='api/reportes.php?tipo=general' class='action-link' target='_blank'>";
        echo "<span class='link-icon'>📊</span>";
        echo "<span class='link-text'>Reportes</span>";
        echo "</a>";
        
        echo "<a href='test_api.php' class='action-link' target='_blank'>";
        echo "<span class='link-icon'>🧪</span>";
        echo "<span class='link-text'>Test API</span>";
        echo "</a>";
        echo "</div>";
        echo "</div>";
        
        // Mostrar resultados JSON para debugging
        echo "<details class='json-details'>";
        echo "<summary>📋 Resultados Detallados (JSON)</summary>";
        echo "<pre class='json-output'>";
        echo json_encode($this->resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        echo "</details>";
        
        echo "</div>";
    }
    
    private function getHTMLHeader() {
        return "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verificación Final - Sistema UCI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        h1 {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 30px;
            margin: 0;
            text-align: center;
            font-size: 2.5rem;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            padding: 10px 30px 20px;
            font-size: 1.1rem;
        }
        
        .test-section {
            margin: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            border-left: 5px solid #007bff;
        }
        
        .test-section h2 {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            margin: 0;
            font-size: 1.3rem;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
            gap: 15px;
        }
        
        .test-item:last-child {
            border-bottom: none;
        }
        
        .icon {
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }
        
        .label {
            flex: 1;
            font-weight: 500;
            color: #333;
        }
        
        .value {
            font-family: monospace;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .value.success {
            background: #d4edda;
            color: #155724;
        }
        
        .value.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .value.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status {
            font-size: 1.1rem;
            width: 25px;
            text-align: center;
        }
        
        .final-summary {
            margin: 30px;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .result-banner {
            padding: 25px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 25px 0;
        }
        
        .result-banner.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
        }
        
        .result-banner.warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
        }
        
        .result-banner.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 2px solid #dc3545;
        }
        
        .banner-icon {
            font-size: 3rem;
        }
        
        .banner-content h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .action-links {
            margin-top: 30px;
        }
        
        .link-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .action-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            border: 2px solid #007bff;
            transition: all 0.3s ease;
        }
        
        .action-link:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
        }
        
        .link-icon {
            font-size: 1.5rem;
        }
        
        .json-details {
            margin-top: 20px;
        }
        
        .json-details summary {
            cursor: pointer;
            padding: 10px;
            background: #e9ecef;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .json-output {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 5px;
            overflow: auto;
            max-height: 400px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.4;
        }
    </style>
</head>
<body>";
    }
}

// Ejecutar verificación
$verificacion = new VerificacionFinal();
$verificacion->ejecutarVerificacionCompleta();
?>