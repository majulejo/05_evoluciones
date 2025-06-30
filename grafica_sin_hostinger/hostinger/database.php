<?php
/**
 * Clase Database para UCI Gráficas - Adaptada a estructura existente
 */

require_once 'config.php';

class Database {
    private $conn;
    private $connected_host;
    
    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        // Probar múltiples hosts
        foreach(DB_HOSTS as $host) {
            try {
                $dsn = "mysql:host={$host};dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];
                
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                // Configurar zona horaria en MySQL
                $this->conn->exec("SET time_zone = '+02:00'");
                
                $this->connected_host = $host;
                error_log("UCI: Conectado exitosamente a {$host}");
                return $this->conn;
                
            } catch(PDOException $e) {
                error_log("UCI: Fallo conexión a {$host}: " . $e->getMessage());
                continue;
            }
        }
        
        throw new Exception("No se pudo conectar a ningún servidor de base de datos");
    }
    
    public function getConnectedHost() {
        return $this->connected_host;
    }
    
    /**
     * Ejecutar una consulta preparada
     */
    public function executeQuery($query, $params = []) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error en consulta: " . $e->getMessage());
            throw new Exception("Error en la consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener un registro por ID
     */
    public function getById($table, $id, $idField = 'id') {
        $query = "SELECT * FROM {$table} WHERE {$idField} = :id LIMIT 1";
        $stmt = $this->executeQuery($query, [':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Insertar un registro
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $fieldsList = implode(', ', $fields);
        
        $query = "INSERT INTO {$table} ({$fieldsList}) VALUES ({$placeholders})";
        
        $params = [];
        foreach($data as $field => $value) {
            $params[':' . $field] = $value;
        }
        
        $stmt = $this->executeQuery($query, $params);
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Actualizar un registro
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        $params = [];
        
        foreach($data as $field => $value) {
            $setClause[] = "{$field} = :{$field}";
            $params[':' . $field] = $value;
        }
        
        $params = array_merge($params, $whereParams);
        $setClause = implode(', ', $setClause);
        
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        return $this->executeQuery($query, $params);
    }
    
    /**
     * Eliminar un registro
     */
    public function delete($table, $where, $whereParams = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        return $this->executeQuery($query, $whereParams);
    }
    
    /**
     * Verificar si existe un registro
     */
    public function exists($table, $where, $whereParams = []) {
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $stmt = $this->executeQuery($query, $whereParams);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Verificar si una tabla existe
     */
    public function tableExists($tableName) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch(Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener múltiples registros
     */
    public function getAll($table, $where = "", $whereParams = [], $orderBy = "", $limit = "") {
        $query = "SELECT * FROM {$table}";
        
        if(!empty($where)) {
            $query .= " WHERE {$where}";
        }
        
        if(!empty($orderBy)) {
            $query .= " ORDER BY {$orderBy}";
        }
        
        if(!empty($limit)) {
            $query .= " LIMIT {$limit}";
        }
        
        $stmt = $this->executeQuery($query, $whereParams);
        return $stmt->fetchAll();
    }
    
    /**
     * Validar que la conexión funciona
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT NOW() as server_time, @@time_zone as timezone, CONNECTION_ID() as connection_id");
            return $stmt->fetch();
        } catch(Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener información de la base de datos
     */
    public function getDbInfo() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT DATABASE() as db_name, VERSION() as version, USER() as user");
            return $stmt->fetch();
        } catch(Exception $e) {
            return null;
        }
    }
    
    /**
     * Crear tablas faltantes (solo las que no existen)
     */
    public function createMissingTables() {
        $conn = $this->getConnection();
        
        // Solo crear las tablas que no existen, respetando la estructura de 'pacientes'
        try {
            // Tabla constantes_vitales
            if (!$this->tableExists('constantes_vitales')) {
                $sql = "CREATE TABLE constantes_vitales (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $conn->exec($sql);
            }
            
            // Tabla oxigenacion_dolor
            if (!$this->tableExists('oxigenacion_dolor')) {
                $sql = "CREATE TABLE oxigenacion_dolor (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    paciente_id INT NOT NULL,
                    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
                    tipo_oxigenacion VARCHAR(50),
                    flujo_oxigeno DECIMAL(5,2),
                    fio2 INT,
                    escala_dolor INT CHECK (escala_dolor BETWEEN 0 AND 10),
                    localizacion_dolor VARCHAR(100),
                    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $conn->exec($sql);
            }
            
            // Tabla perdidas
            if (!$this->tableExists('perdidas')) {
                $sql = "CREATE TABLE perdidas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    paciente_id INT NOT NULL,
                    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
                    tipo_perdida ENUM('orina', 'heces', 'vomito', 'drenaje', 'otros'),
                    cantidad_ml INT,
                    observaciones TEXT,
                    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $conn->exec($sql);
            }
            
            // Tabla balances_diarios
            if (!$this->tableExists('balances_diarios')) {
                $sql = "CREATE TABLE balances_diarios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    paciente_id INT NOT NULL,
                    fecha DATE NOT NULL,
                    ingresos_totales INT DEFAULT 0,
                    egresos_totales INT DEFAULT 0,
                    balance_hidrico INT AS (ingresos_totales - egresos_totales) STORED,
                    peso_kg DECIMAL(5,2),
                    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_paciente_fecha (paciente_id, fecha)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $conn->exec($sql);
            }
            
            // Insertar configuración inicial si no existe
            $this->insertInitialConfig();
            
            return true;
        } catch(PDOException $e) {
            error_log("Error creando tablas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insertar configuración inicial
     */
    private function insertInitialConfig() {
        // Crear tabla configuración si no existe
        if (!$this->tableExists('configuracion')) {
            $sql = "CREATE TABLE configuracion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(50) UNIQUE NOT NULL,
                valor TEXT,
                descripcion VARCHAR(255),
                fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->getConnection()->exec($sql);
        }
        
        $configs = [
            ['nombre_hospital', 'UCI Hospital', 'Nombre del hospital'],
            ['timezone', 'Europe/Madrid', 'Zona horaria'],
            ['version_sistema', '1.0.0', 'Versión del sistema']
        ];
        
        foreach($configs as $config) {
            try {
                $exists = $this->exists('configuracion', 'clave = :clave', [':clave' => $config[0]]);
                if (!$exists) {
                    $this->insert('configuracion', [
                        'clave' => $config[0],
                        'valor' => $config[1],
                        'descripcion' => $config[2]
                    ]);
                }
            } catch(Exception $e) {
                error_log("Error insertando configuración inicial: " . $e->getMessage());
            }
        }
    }
}

/**
 * Clase para respuestas JSON estandarizadas
 */
class ApiResponse {
    public static function success($data = null, $message = "Operación exitosa") {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    public static function error($message = "Error en la operación", $code = 500, $details = null) {
        http_response_code($code);
        return [
            'success' => false,
            'message' => $message,
            'error_code' => $code,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    public static function json($response) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

/**
 * Función para validar métodos HTTP
 */
function validateMethod($allowedMethods) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if($method === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    if(!in_array($method, $allowedMethods)) {
        ApiResponse::json(ApiResponse::error("Método no permitido", 405));
    }
    
    return $method;
}

/**
 * Función para obtener datos JSON del body
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if(json_last_error() !== JSON_ERROR_NONE) {
        ApiResponse::json(ApiResponse::error("JSON inválido", 400));
    }
    
    return $data ?? [];
}

/**
 * Función para validar campos requeridos
 */
function validateRequired($data, $requiredFields) {
    $missing = [];
    
    foreach($requiredFields as $field) {
        if(!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if(!empty($missing)) {
        ApiResponse::json(ApiResponse::error(
            "Campos requeridos faltantes: " . implode(', ', $missing), 
            400, 
            ['missing_fields' => $missing]
        ));
    }
}

/**
 * Función para logging
 */
function logAction($action, $data = null, $user = 'system') {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'user' => $user,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'data' => $data
    ];
    
    error_log("UCI_API: " . json_encode($log));
}
?>