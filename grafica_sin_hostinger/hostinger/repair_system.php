<?php
/**
 * Script de Reparación - Adaptado a la estructura existente
 */

require_once 'database.php';

class SystemRepair {
    private $db;
    private $results = [];
    
    public function __construct() {
        $this->db = new Database();
        $this->results['timestamp'] = date('Y-m-d H:i:s');
        $this->results['timezone'] = date_default_timezone_get();
    }
    
    public function runCompleteRepair() {
        echo "<html><head><title>Reparación Sistema UCI</title>";
        echo "<style>body{font-family:Arial;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;} .step{margin:20px 0;padding:15px;border-left:4px solid #007bff;background:#f8f9fa;}</style>";
        echo "</head><body>";
        echo "<h1>🔧 Reparación Sistema UCI</h1>";
        echo "<p>Adaptando a la estructura existente...</p>";
        
        // Paso 1: Probar conexión
        $this->testConnection();
        
        // Paso 2: Verificar estructura actual
        $this->checkCurrentStructure();
        
        // Paso 3: Crear tablas faltantes
        $this->createMissingTables();
        
        // Paso 4: Verificar permisos
        $this->checkPermissions();
        
        // Paso 5: Insertar datos de prueba adaptados
        $this->insertAdaptedTestData();
        
        // Paso 6: Verificar sistema completo
        $this->verifySystem();
        
        echo "<h2>🎯 Resumen Final</h2>";
        $this->showResults();
        
        echo "</body></html>";
    }
    
    private function testConnection() {
        echo "<div class='step'>";
        echo "<h3>📡 Paso 1: Probando Conexión</h3>";
        
        try {
            $connectionInfo = $this->db->testConnection();
            
            if ($connectionInfo) {
                echo "<p class='success'>✅ Conexión exitosa al host: " . $this->db->getConnectedHost() . "</p>";
                echo "<p class='info'>🕐 Hora del servidor: " . $connectionInfo['server_time'] . "</p>";
                echo "<p class='info'>🌍 Zona horaria DB: " . $connectionInfo['timezone'] . "</p>";
                
                $this->results['connection'] = [
                    'status' => true,
                    'host' => $this->db->getConnectedHost(),
                    'server_time' => $connectionInfo['server_time'],
                    'timezone' => $connectionInfo['timezone']
                ];
            } else {
                throw new Exception("No se pudo conectar a la base de datos");
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
            $this->results['connection'] = ['status' => false, 'error' => $e->getMessage()];
        }
        
        echo "</div>";
    }
    
    private function checkCurrentStructure() {
        echo "<div class='step'>";
        echo "<h3>🔍 Paso 2: Verificando Estructura Actual</h3>";
        
        try {
            $conn = $this->db->getConnection();
            
            // Verificar estructura de pacientes
            if ($this->db->tableExists('pacientes')) {
                $stmt = $conn->query("DESCRIBE pacientes");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<p class='success'>✅ Tabla 'pacientes' existe con columnas: " . implode(', ', $columns) . "</p>";
                $this->results['existing_structure']['pacientes'] = $columns;
            } else {
                echo "<p class='error'>❌ Tabla 'pacientes' no existe</p>";
                $this->results['existing_structure']['pacientes'] = false;
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error verificando estructura: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    private function createMissingTables() {
        echo "<div class='step'>";
        echo "<h3>🏗️ Paso 3: Creando Tablas Faltantes</h3>";
        
        try {
            $success = $this->db->createMissingTables();
            
            if ($success) {
                echo "<p class='success'>✅ Tablas creadas/verificadas exitosamente</p>";
                
                // Verificar cada tabla
                $tables = ['pacientes', 'constantes_vitales', 'oxigenacion_dolor', 'perdidas', 'balances_diarios', 'configuracion'];
                
                foreach ($tables as $table) {
                    $exists = $this->db->tableExists($table);
                    if ($exists) {
                        echo "<p class='success'>✓ Tabla '{$table}' verificada</p>";
                        $this->results['tables'][$table] = true;
                    } else {
                        echo "<p class='error'>✗ Tabla '{$table}' NO existe</p>";
                        $this->results['tables'][$table] = false;
                    }
                }
                
            } else {
                echo "<p class='error'>❌ Error en la creación/verificación de tablas</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
            $this->results['tables_error'] = $e->getMessage();
        }
        
        echo "</div>";
    }
    
    private function checkPermissions() {
        echo "<div class='step'>";
        echo "<h3>🔐 Paso 4: Verificando Permisos</h3>";
        
        $permissions = [
            'SELECT' => "SELECT 1 as test",
            'INSERT' => "INSERT INTO configuracion (clave, valor, descripcion) VALUES ('test_permission', 'test', 'Test de permisos')",
            'UPDATE' => "UPDATE configuracion SET valor = 'updated' WHERE clave = 'test_permission'",
            'DELETE' => "DELETE FROM configuracion WHERE clave = 'test_permission'"
        ];
        
        foreach ($permissions as $operation => $query) {
            try {
                $this->db->executeQuery($query);
                echo "<p class='success'>✅ Permiso {$operation}: OK</p>";
                $this->results['permissions'][$operation] = true;
            } catch (Exception $e) {
                echo "<p class='error'>❌ Permiso {$operation}: FALLO - " . $e->getMessage() . "</p>";
                $this->results['permissions'][$operation] = false;
            }
        }
        
        echo "</div>";
    }
    
    private function insertAdaptedTestData() {
        echo "<div class='step'>";
        echo "<h3>📊 Paso 5: Insertando Datos de Prueba (Adaptados)</h3>";
        
        try {
            // Insertar paciente con la estructura correcta (solo nombre completo)
            $patientData = [
                'nombre' => 'María González López', // Nombre completo en un solo campo
                'edad' => 45,
                'peso' => 68.5,
                'historia_clinica' => 'Historia clínica de prueba',
                'cama' => 3,
                'fecha_ingreso' => date('Y-m-d H:i:s'),
                'hoja_clinica' => 1,
                'fecha_grafica' => date('Y-m-d'),
                'activo' => 1
            ];
            
            $patientId = $this->db->insert('pacientes', $patientData);
            echo "<p class='success'>✅ Paciente de prueba creado (ID: {$patientId})</p>";
            
            // Insertar constantes vitales de prueba
            if ($this->db->tableExists('constantes_vitales')) {
                $vitalsData = [
                    'paciente_id' => $patientId,
                    'temperatura' => 36.8,
                    'presion_sistolica' => 120,
                    'presion_diastolica' => 80,
                    'frecuencia_cardiaca' => 72,
                    'frecuencia_respiratoria' => 16,
                    'saturacion_oxigeno' => 98
                ];
                
                $vitalsId = $this->db->insert('constantes_vitales', $vitalsData);
                echo "<p class='success'>✅ Constantes vitales de prueba creadas (ID: {$vitalsId})</p>";
            }
            
            $this->results['test_data'] = [
                'patient_id' => $patientId,
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error insertando datos de prueba: " . $e->getMessage() . "</p>";
            $this->results['test_data'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        echo "</div>";
    }
    
    private function verifySystem() {
        echo "<div class='step'>";
        echo "<h3>🔍 Paso 6: Verificación Final del Sistema</h3>";
        
        try {
            // Verificar que podemos leer datos
            $patients = $this->db->getAll('pacientes', '', [], 'id DESC', '5');
            echo "<p class='success'>✅ Lectura de pacientes: " . count($patients) . " registros encontrados</p>";
            
            // Mostrar algunos datos de ejemplo
            if (count($patients) > 0) {
                foreach ($patients as $patient) {
                    echo "<p class='info'>👤 Paciente: " . $patient['nombre'] . " (Cama: " . $patient['cama'] . ")</p>";
                }
            }
            
            // Verificar configuración
            $config = $this->db->getAll('configuracion');
            echo "<p class='success'>✅ Configuración del sistema: " . count($config) . " parámetros configurados</p>";
            
            // Mostrar información de la base de datos
            $dbInfo = $this->db->getDbInfo();
            if ($dbInfo) {
                echo "<p class='info'>📊 Base de datos: " . $dbInfo['db_name'] . "</p>";
                echo "<p class='info'>🔢 Versión MySQL: " . $dbInfo['version'] . "</p>";
                echo "<p class='info'>👤 Usuario conectado: " . $dbInfo['user'] . "</p>";
            }
            
            $this->results['verification'] = [
                'patients_count' => count($patients),
                'config_count' => count($config),
                'db_info' => $dbInfo,
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error en verificación: " . $e->getMessage() . "</p>";
            $this->results['verification'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        echo "</div>";
    }
    
    private function showResults() {
        $totalTests = 0;
        $passedTests = 0;
        
        // Contar resultados
        if (isset($this->results['connection']['status']) && $this->results['connection']['status']) $passedTests++;
        $totalTests++;
        
        if (isset($this->results['tables'])) {
            foreach ($this->results['tables'] as $table => $status) {
                if ($status) $passedTests++;
                $totalTests++;
            }
        }
        
        if (isset($this->results['permissions'])) {
            foreach ($this->results['permissions'] as $permission => $status) {
                if ($status) $passedTests++;
                $totalTests++;
            }
        }
        
        if (isset($this->results['test_data']['status']) && $this->results['test_data']['status'] === 'success') $passedTests++;
        $totalTests++;
        
        if (isset($this->results['verification']['status']) && $this->results['verification']['status'] === 'success') $passedTests++;
        $totalTests++;
        
        $percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
        
        echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;border:2px solid #28a745;'>";
        echo "<h3 style='color:#155724;'>🎯 Resultado Final</h3>";
        echo "<p><strong>Tests pasados:</strong> {$passedTests}/{$totalTests} ({$percentage}%)</p>";
        
        if ($percentage >= 90) {
            echo "<p class='success'><strong>🎉 ¡SISTEMA FUNCIONANDO PERFECTAMENTE!</strong></p>";
            echo "<p>Tu sistema UCI está adaptado correctamente a tu estructura de base de datos.</p>";
        } elseif ($percentage >= 75) {
            echo "<p style='color:#856404;'><strong>⚠️ Sistema mayormente funcional</strong></p>";
            echo "<p>La mayoría de componentes están funcionando bien.</p>";
        } else {
            echo "<p class='error'><strong>❌ Sistema requiere atención adicional</strong></p>";
            echo "<p>Revisa los errores reportados arriba.</p>";
        }
        
        echo "</div>";
        
        // Mostrar JSON de resultados para debugging
        echo "<details style='margin-top:20px;'>";
        echo "<summary>📋 Detalles Técnicos (JSON)</summary>";
        echo "<pre style='background:#f8f9fa;padding:15px;border-radius:5px;overflow:auto;'>";
        echo json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        echo "</details>";
    }
}

// Ejecutar reparación
$repair = new SystemRepair();
$repair->runCompleteRepair();
?>