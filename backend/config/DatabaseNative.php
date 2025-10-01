<?php
/**
 * Configuración de Base de Datos - Famicitas (Funciones nativas MySQL)
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
            // Usar mysql_connect (funciones nativas)
            $this->conn = mysql_connect($this->host . ':' . $this->port, $this->username, $this->password);
            
            if (!$this->conn) {
                throw new Exception("Error de conexión: " . mysql_error());
            }
            
            // Seleccionar base de datos
            $db_selected = mysql_select_db($this->db_name, $this->conn);
            if (!$db_selected) {
                throw new Exception("Error seleccionando DB: " . mysql_error());
            }
            
            // Configurar charset
            mysql_query("SET NAMES utf8", $this->conn);
            
        } catch(Exception $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }

        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            mysql_close($this->conn);
        }
    }
    
    public function query($sql) {
        if (!$this->conn) {
            $this->getConnection();
        }
        
        $result = mysql_query($sql, $this->conn);
        if (!$result) {
            throw new Exception("Error en query: " . mysql_error());
        }
        
        return $result;
    }
    
    public function fetch_assoc($result) {
        return mysql_fetch_assoc($result);
    }
    
    public function num_rows($result) {
        return mysql_num_rows($result);
    }
    
    public function real_escape_string($string) {
        return mysql_real_escape_string($string, $this->conn);
    }
}
?>