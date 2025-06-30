<?php
/**
 * Script de pruebas completo para la API UCI
 */

require_once 'database.php';

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

class ApiTester {
    private $db;
    private $results = [];
    
    public function __construct() {
        $this->db = new Database();
        $this->results['timestamp'] = date('Y-m-d H:i:s');
        $this->results['server_info'] = [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'timezone' => date_default_timezone_get()
        ];
    }
    
    public function runAllTests() {
        echo "<html><head><title>Test API UCI</title>";
        echo "<style>";
        echo "body{font-family:Arial;margin:40px;background:#f5f5f5;}";
        echo ".container{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
        echo ".success{color:green;} .error{color:red;} .info{color:blue;}";
        echo ".test-section{margin:20px 0;padding:15px;border-left:4px solid #007bff;background:#f8f9fa;}";
        echo ".json-output{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:5px;overflow:auto;font-family:monospace;}";
        echo "</style>";
        echo "</head><body>";
        echo "<div class='container'>";
        echo "<h1>🧪 Test Completo API UCI</h1>";
        
        $this->testConnection();
        $this->testTables();
        $this->testCrud();
        $this->testEndpoints();
        $this->showFinalResults();
        
        echo "</div></body></html>";
    }
    
    private function testConnection() {
        echo "<div class='test-section'>";
        echo "<h3>📡 Test 1: Conexión a Base de Datos</h3>";
        
        try {
            $connectionInfo = $this->db->testConnection();
            
            if ($connectionInfo) {
                echo "<p class='success'>✅ Conexión exitosa</p>";
                echo "<p class='info'>🖥️ Host: " . $this->db->getConnectedHost() . "</p>";
                echo "<p class='info'>⏰ Hora servidor: " . $connectionInfo['server_time'] . "</p>";
                echo "<p class='info'>🌍 Zona horaria: " . $connectionInfo['timezone'] . "</p>";
                
                $this->results['tests']['connection'] = [
                    'status' => 'PASS',
                    'details' => $connectionInfo
                ];
            } else {
                throw new Exception("Conexión fallida");
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
            $this->results['tests']['connection'] = [
                'status' => 'FAIL',
                'error' => $e->getMessage()
            ];
        }
        
        echo "</div>";
    }
    
    private function testTables() {
        echo "<div class='test-section'>";
        echo "<h3>🗄️ Test 2: Verificación de Tablas</h3>";
        
        $requiredTables = [
            'pacientes' => 'Tabla principal de pacientes',
            'constantes_vitales' => 'Constantes vitales de pacientes',
            'oxigenacion_dolor' => 'Datos de oxigenación y dolor',
            'perdidas' => 'Registro de pérdidas',
            'balances_diarios' => 'Balances hídricos diarios',
            'configuracion' => 'Configuración del sistema'
        ];
        
        $tablesOk = 0;
        $totalTables = count($requiredTables);
        
        foreach ($requiredTables as $table => $description) {
            try {
                $exists = $this->db->tableExists($table);
                if ($exists) {
                    echo "<p class='success'>✅ {$table}: OK - {$description}</p>";
                    $tablesOk++;
                    $this->results['tests']['tables'][$table] = 'PASS';
                } else {
                    echo "<p class='error'>❌ {$table}: FALTA - {$description}</p>";
                    $this->results['tests']['tables'][$table] = 'FAIL';
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ {$table}: ERROR - " . $e->getMessage() . "</p>";
                $this->results['tests']['tables'][$table] = 'ERROR';
            }
        }
        
        echo "<p class='info'>📊 Resumen: {$tablesOk}/{$totalTables} tablas OK</p>";
        $this->results['tests']['tables_summary'] = [
            'total' => $totalTables,
            'passed' => $tablesOk,
            'percentage' => round(($tablesOk / $totalTables) * 100, 1)
        ];
        
        echo "</div>";
    }
    
    private function testCrud() {
        echo "<div class='test-section'>";
        echo "<h3>🔄 Test 3: Operaciones CRUD</h3>";
        
        try {
            // Test CREATE
            $testPatient = [
                'nombre' => 'Paciente Test API',
                'edad' => 50,
                'peso' => 70.5,
                'historia_clinica' => 'Test desde API',
                'cama' => 999,
                'fecha_ingreso' => date('Y-m-d H:i:s'),
                'hoja_clinica' => 1,
                'fecha_grafica' => date('Y-m-d'),
                'activo' => 1
            ];
            
            $patientId = $this->db->insert('pacientes', $testPatient);
            echo "<p class='success'>✅ CREATE: Paciente creado (ID: {$patientId})</p>";
            $this->results['tests']['crud']['create'] = 'PASS';
            
            // Test READ
            $retrievedPatient = $this->db->getById('pacientes', $patientId);
            if ($retrievedPatient && $retrievedPatient['nombre'] === 'Paciente Test API') {
                echo "<p class='success'>✅ READ: Paciente recuperado correctamente</p>";
                $this->results['tests']['crud']['read'] = 'PASS';
            } else {
                throw new Exception("Error en READ");
            }
            
            // Test UPDATE
            $updateData = ['peso' => 72.0, 'historia_clinica' => 'Actualizado desde API'];
            $this->db->update('pacientes', $updateData, 'id = :id', [':id' => $patientId]);
            
            $updatedPatient = $this->db->getById('pacientes', $patientId);
            if ($updatedPatient['peso'] == 72.0) {
                echo "<p class='success'>✅ UPDATE: Paciente actualizado correctamente</p>";
                $this->results['tests']['crud']['update'] = 'PASS';
            } else {
                throw new Exception("Error en UPDATE");
            }
            
            // Test DELETE
            $this->db->delete('pacientes', 'id = :id', [':id' => $patientId]);
            $deletedPatient = $this->db->getById('pacientes', $patientId);
            
            if (!$deletedPatient) {
                echo "<p class='success'>✅ DELETE: Paciente eliminado correctamente</p>";
                $this->results['tests']['crud']['delete'] = 'PASS';
            } else {
                throw new Exception("Error en DELETE");
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error en CRUD: " . $e->getMessage() . "</p>";
            $this->results['tests']['crud']['error'] = $e->getMessage();
        }
        
        echo "</div>";
    }
    
    private function testEndpoints() {
        echo "<div class='test-section'>";
        echo "<h3>🌐 Test 4: Endpoints de API</h3>";
        
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        
        $endpoints = [
            'api/pacientes.php' => 'Gestión de pacientes',
            'api/constantes.php' => 'Constantes vitales',
            'api/graficas.php' => 'Generación de gráficas',
            'api/config.php' => 'Configuración del sistema'
        ];
        
        foreach ($endpoints as $endpoint => $description) {
            $fullUrl = $baseUrl . '/' . $endpoint;
            $exists = file_exists($endpoint);
            
            if ($exists) {
                echo "<p class='success'>✅ {$endpoint}: OK - {$description}</p>";
                $this->results['tests']['endpoints'][$endpoint] = 'EXISTS';
            } else {
                echo "<p class='error'>❌ {$endpoint}: FALTA - {$description}</p>";
                $this->results['tests']['endpoints'][$endpoint] = 'MISSING';
            }
        }
        
        echo "</div>";
    }
    
    private function showFinalResults() {
        echo "<div class='test-section'>";
        echo "<h3>🎯 Resultados Finales</h3>";
        
        // Calcular estadísticas
        $totalTests = 0;
        $passedTests = 0;
        
        // Conexión
        if (isset($this->results['tests']['connection']['status']) && 
            $this->results['tests']['connection']['status'] === 'PASS') {
            $passedTests++;
        }
        $totalTests++;
        
        // Tablas
        if (isset($this->results['tests']['tables_summary'])) {
            $tablesPercentage = $this->results['tests']['tables_summary']['percentage'];
            if ($tablesPercentage >= 80) $passedTests++;
            $totalTests++;
        }
        
        // CRUD
        if (isset($this->results['tests']['crud'])) {
            $crudOperations = ['create', 'read', 'update', 'delete'];
            $crudPassed = 0;
            foreach ($crudOperations as $op) {
                if (isset($this->results['tests']['crud'][$op]) && 
                    $this->results['tests']['crud'][$op] === 'PASS') {
                    $crudPassed++;
                }
            }
            if ($crudPassed >= 3) $passedTests++; // Al menos 3 de 4 operaciones
            $totalTests++;
        }
        
        $successPercentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
        
        echo "<div style='background:#e8f5e8;padding:20px;border-radius:8px;border:2px solid #28a745;margin:20px 0;'>";
        echo "<h4>📊 Estadísticas Generales</h4>";
        echo "<p><strong>Tests pasados:</strong> {$passedTests}/{$totalTests} ({$successPercentage}%)</p>";
        
        if ($successPercentage >= 85) {
            echo "<p class='success'><strong>🎉 ¡EXCELENTE! Sistema funcionando correctamente</strong></p>";
            echo "<p>Tu API UCI está lista para usar en producción.</p>";
        } elseif ($successPercentage >= 70) {
            echo "<p style='color:#856404;'><strong>⚠️ Sistema mayormente funcional</strong></p>";
            echo "<p>Algunos componentes necesitan atención, pero es usable.</p>";
        } else {
            echo "<p class='error'><strong>❌ Sistema requiere correcciones</strong></p>";
            echo "<p>Revisa los errores reportados y ejecuta las correcciones necesarias.</p>";
        }
        
        echo "</div>";
        
        // Mostrar resultados completos en JSON
        echo "<h4>📋 Resultados Completos (JSON)</h4>";
        echo "<div class='json-output'>";
        echo json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</div>";
        
        echo "</div>";
    }
    
    public function getJsonResults() {
        return json_encode($this->results, JSON_UNESCAPED_UNICODE);
    }
}

// Ejecutar según el parámetro
$format = $_GET['format'] ?? 'html';

$tester = new ApiTester();

if ($format === 'json') {
    // Solo resultados en JSON
    $tester->runAllTests();
    echo $tester->getJsonResults();
} else {
    // Interfaz HTML completa
    $tester->runAllTests();
}
?>