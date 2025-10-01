<?php
/**
 * Configuración de Base de Datos - Famicitas
 */

class Database {
    private $host = '193.203.166.24';
    private $db_name = 'u573706850_famicitas';
    private $username = 'u573706850_famicitas';
    private $password = 'Famicitas123';
    private $port = '3306';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . 
                ";port=" . $this->port . 
                ";dbname=" . $this->db_name . 
                ";charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
?>
