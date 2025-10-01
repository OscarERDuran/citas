<?php
/**
 * Configuración de Base de Datos - Famicitas (MySQLi version)
 */

class Database {
    private $host = '193.203.166.24';
    private $db_name = 'u573706850_famicitas';
    private $username = 'u573706850_famicitas';
    private $password = 'Famicitas123';
    private $port = '3306';
    public $conn;

    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }

        try {
            // Desactivar reportes de error para manejarlos manualmente
            mysqli_report(MYSQLI_REPORT_OFF);
            
            // Intentar conexión con mysqli
            $this->conn = @new mysqli($this->host, $this->username, $this->password, $this->db_name, $this->port);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Configurar charset
            if (!$this->conn->set_charset("utf8mb4")) {
                $this->conn->set_charset("utf8");
            }
            
        } catch(Exception $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $exception->getMessage());
        }

        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    /**
     * Wrapper para PDO-like functionality con mysqli
     */
    public function query($sql) {
        if (!$this->conn) {
            $this->getConnection();
        }
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            throw new Exception("Query failed: " . $this->conn->error);
        }
        
        return $result;
    }
    
    /**
     * Prepare statement wrapper
     */
    public function prepare($sql) {
        if (!$this->conn) {
            $this->getConnection();
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        return $stmt;
    }
    
    /**
     * Execute SQL file
     */
    public function executeSqlFile($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("SQL file not found: $filePath");
        }
        
        $sql = file_get_contents($filePath);
        
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split into statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if (!$this->conn->query($statement)) {
                    throw new Exception("SQL Error: " . $this->conn->error . " in statement: " . substr($statement, 0, 100));
                }
            }
        }
        
        return true;
    }
}
?>
