<?php
/**
 * Script para corregir la estructura de las tablas
 */

require_once 'database.php';

class TableStructureFix {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function fixAllTables() {
        echo "<html><head><title>Corrección de Estructura</title>";
        echo "<style>body{font-family:Arial;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;} .step{margin:20px 0;padding:15px;border-left:4px solid #28a745;background:#f8f9fa;}</style>";
        echo "</head><body>";
        echo "<h1>🔧 Corrección de Estructura de Tablas</h1>";
        
        // Paso 1: Verificar estructura actual
        $this->checkCurrentStructure();
        
        // Paso 2: Recrear tablas con estructura correcta
        $this->recreateTables();
        
        // Paso 3: Verificar corrección
        $this->verifyFix();
        
        echo "</body></html>";
    }
    
    private function checkCurrentStructure() {
        echo "<div class='step'>";
        echo "<h3>🔍 Verificando Estructura Actual</h3>";
        
        try {
            $conn = $this->db->getConnection();
            
            // Verificar estructura de la tabla pacientes
            $stmt = $conn->query("DESCRIBE pacientes");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<p class='info'>📋 Columnas actuales en 'pacientes': " . implode(', ', $columns) . "</p>";
            
            // Verificar si falta la columna 'apellidos'
            if (!in_array('apellidos', $columns)) {
                echo "<p class='error'>❌ Falta la columna 'apellidos' en la tabla pacientes</p>";
            } else {
                echo "<p class='success'>✅ La columna 'apellidos' existe</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error verificando estructura: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    private function recreateTables() {
        echo "<div class='step'>";
        echo "<h3>🏗️ Recreando Tablas con Estructura Correcta</h3>";
        
        try {
            $conn = $this->db->getConnection();
            
            // Primero, eliminar las tablas en el orden correcto (por las foreign keys)
            $dropQueries = [
                "DROP TABLE IF EXISTS balances_diarios",
                "DROP TABLE IF EXISTS perdidas", 
                "DROP TABLE IF EXISTS oxigenacion_dolor",
                "DROP TABLE IF EXISTS constantes_vitales",
                "DROP TABLE IF EXISTS pacientes",
                "DROP TABLE IF EXISTS configuracion"
            ];
            
            foreach ($dropQueries as $query) {
                $conn->exec($query);
                echo "<p class='info'>🗑️ Eliminando tabla existente...</p>";
            }
            
            // Ahora recrear todas las tablas con la estructura correcta
            $createSQL = "
            CREATE TABLE pacientes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                apellidos VARCHAR(100) NOT NULL,
                fecha_nacimiento DATE,
                genero ENUM('M', 'F') NOT NULL,
                numero_cama VARCHAR(10),
                fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
                estado ENUM('activo', 'alta', 'transferido') DEFAULT 'activo',
                observaciones TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE constantes_vitales (
                id INT AUTO_INCREMENT PRIMARY KEY,
                paciente_id INT NOT NULL,
                fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
                temperatura DECIMAL(4,2),
                presion_sistolica INT,
                presion_diastolica INT,
                frecuencia_cardiaca INT,
                frecuencia_respiratoria INT,
                saturacion_oxigeno INT,
                FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE oxigenacion_dolor (
                id INT AUTO_INCREMENT PRIMARY KEY,
                paciente_id INT NOT NULL,
                fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
                tipo_oxigenacion VARCHAR(50),
                flujo_oxigeno DECIMAL(5,2),
                fio2 INT,
                escala_dolor INT CHECK (escala_dolor BETWEEN 0 AND 10),
                localizacion_dolor VARCHAR(100),
                FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE perdidas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                paciente_id INT NOT NULL,
                fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
                tipo_perdida ENUM('orina', 'heces', 'vomito', 'drenaje', 'otros'),
                cantidad_ml INT,
                observaciones TEXT,
                FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE balances_diarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                paciente_id INT NOT NULL,
                fecha DATE NOT NULL,
                ingresos_totales INT DEFAULT 0,
                egresos_totales INT DEFAULT 0,
                balance_hidrico INT AS (ingresos_totales - egresos_totales) STORED,
                peso_kg DECIMAL(5,2),
                FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
                UNIQUE KEY unique_paciente_fecha (paciente_id, fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE configuracion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(50) UNIQUE NOT NULL,
                valor TEXT,
                descripcion VARCHAR(255),
                fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            // Ejecutar todas las consultas de creación
            $queries = explode(';', $createSQL);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $conn->exec($query);
                }
            }
            
            echo "<p class='success'>✅ Todas las tablas recreadas con la estructura correcta</p>";
            
            // Insertar configuración inicial
            $this->insertInitialConfig($conn);
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error recreando tablas: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    private function insertInitialConfig($conn) {
        echo "<h4>📝 Insertando Configuración Inicial</h4>";
        
        $configs = [
            ['nombre_hospital', 'UCI Hospital', 'Nombre del hospital'],
            ['timezone', 'Europe/Madrid', 'Zona horaria'],
            ['version_sistema', '1.0.0', 'Versión del sistema']
        ];
        
        foreach ($configs as $config) {
            try {
                $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?)");
                $stmt->execute($config);
                echo "<p class='success'>✓ Configuración '{$config[0]}' insertada</p>";
            } catch (Exception $e) {
                echo "<p class='error'>✗ Error insertando '{$config[0]}': " . $e->getMessage() . "</p>";
            }
        }
    }
    
    private function verifyFix() {
        echo "<div class='step'>";
        echo "<h3>✅ Verificando Corrección</h3>";
        
        try {
            // Probar insertar un paciente de prueba
            $patientData = [
                'nombre' => 'María',
                'apellidos' => 'González López',
                'fecha_nacimiento' => '1975-08-20',
                'genero' => 'F',
                'numero_cama' => 'UCI-002',
                'observaciones' => 'Paciente de prueba después de la corrección'
            ];
            
            $patientId = $this->db->insert('pacientes', $patientData);
            echo "<p class='success'>✅ Paciente de prueba insertado correctamente (ID: {$patientId})</p>";
            
            // Probar insertar constantes vitales
            $vitalsData = [
                'paciente_id' => $patientId,
                'temperatura' => 37.2,
                'presion_sistolica' => 130,
                'presion_diastolica' => 85,
                'frecuencia_cardiaca' => 78,
                'frecuencia_respiratoria' => 18,
                'saturacion_oxigeno' => 97
            ];
            
            $vitalsId = $this->db->insert('constantes_vitales', $vitalsData);
            echo "<p class='success'>✅ Constantes vitales insertadas correctamente (ID: {$vitalsId})</p>";
            
            // Verificar que podemos leer los datos
            $patients = $this->db->getAll('pacientes');
            echo "<p class='success'>✅ Lectura de datos: " . count($patients) . " pacientes encontrados</p>";
            
            echo "<div style='background:#d4edda;padding:15px;border-radius:5px;border:1px solid #c3e6cb;margin-top:20px;'>";
            echo "<h4 style='color:#155724;'>🎉 ¡CORRECCIÓN COMPLETADA!</h4>";
            echo "<p>La estructura de las tablas ha sido corregida y el sistema está funcionando correctamente.</p>";
            echo "<p><strong>Puedes volver a ejecutar el repair_system.php para verificar que todo esté al 100%.</strong></p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error en la verificación: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
}

// Ejecutar corrección
$fix = new TableStructureFix();
$fix->fixAllTables();
?>