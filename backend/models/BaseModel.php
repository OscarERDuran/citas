<?php
/**
 * Modelo Base - Funcionalidades comunes para todos los modelos
 */

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Obtener todos los registros
     */
    public function getAll($conditions = [], $orderBy = null, $limit = null, $offset = null) {
        try {
            $sql = "SELECT * FROM {$this->table}";
            
            if (!empty($conditions)) {
                $whereClause = $this->buildWhereClause($conditions);
                $sql .= " WHERE " . $whereClause;
            }
            
            if ($orderBy) {
                $sql .= " ORDER BY " . $orderBy;
            }
            
            if ($limit) {
                $sql .= " LIMIT " . $limit;
                if ($offset) {
                    $sql .= " OFFSET " . $offset;
                }
            }
            
            $stmt = $this->db->prepare($sql);
            $this->bindConditions($stmt, $conditions);
            $stmt->execute();
            
            $results = $stmt->fetchAll();
            return $this->hideFields($results);
            
        } catch (PDOException $e) {
            error_log("Error en getAll: " . $e->getMessage());
            throw new Exception("Error al obtener registros");
        }
    }
    
    /**
     * Obtener un registro por ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            $result = $stmt->fetch();
            return $result ? $this->hideFields([$result])[0] : null;
            
        } catch (PDOException $e) {
            error_log("Error en getById: " . $e->getMessage());
            throw new Exception("Error al obtener registro");
        }
    }
    
    /**
     * Crear un nuevo registro
     */
    public function create($data) {
        try {
            $data = $this->filterFillable($data);
            $fields = array_keys($data);
            $placeholders = ':' . implode(', :', $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Error en create: " . $e->getMessage());
            throw new Exception("Error al crear registro");
        }
    }
    
    /**
     * Actualizar un registro
     */
    public function update($id, $data) {
        try {
            $data = $this->filterFillable($data);
            $fields = array_keys($data);
            $setClause = implode(' = ?, ', $fields) . ' = ?';
            
            $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            
            $values = array_values($data);
            $values[] = $id;
            
            return $stmt->execute($values);
            
        } catch (PDOException $e) {
            error_log("Error en update: " . $e->getMessage());
            throw new Exception("Error al actualizar registro");
        }
    }
    
    /**
     * Eliminar un registro
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Error en delete: " . $e->getMessage());
            throw new Exception("Error al eliminar registro");
        }
    }
    
    /**
     * Contar registros
     */
    public function count($conditions = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            
            if (!empty($conditions)) {
                $whereClause = $this->buildWhereClause($conditions);
                $sql .= " WHERE " . $whereClause;
            }
            
            $stmt = $this->db->prepare($sql);
            $this->bindConditions($stmt, $conditions);
            $stmt->execute();
            
            return $stmt->fetch()['total'];
            
        } catch (PDOException $e) {
            error_log("Error en count: " . $e->getMessage());
            throw new Exception("Error al contar registros");
        }
    }
    
    /**
     * Filtrar campos permitidos
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Ocultar campos sensibles
     */
    protected function hideFields($data) {
        if (empty($this->hidden)) {
            return $data;
        }
        
        foreach ($data as &$row) {
            foreach ($this->hidden as $field) {
                unset($row[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Construir clausula WHERE
     */
    protected function buildWhereClause($conditions) {
        $clauses = [];
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $clauses[] = "{$field} IN ({$placeholders})";
            } else {
                $clauses[] = "{$field} = ?";
            }
        }
        return implode(' AND ', $clauses);
    }
    
    /**
     * Vincular condiciones a la consulta
     */
    protected function bindConditions($stmt, $conditions) {
        $paramIndex = 1;
        foreach ($conditions as $value) {
            if (is_array($value)) {
                foreach ($value as $subValue) {
                    $stmt->bindValue($paramIndex++, $subValue);
                }
            } else {
                $stmt->bindValue($paramIndex++, $value);
            }
        }
    }
    
    /**
     * Validar datos
     */
    public function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $error = $this->validateField($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validar campo individual
     */
    protected function validateField($field, $value, $rule) {
        switch ($rule) {
            case 'required':
                return empty($value) ? "El campo {$field} es requerido" : null;
            case 'email':
                return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "El campo {$field} debe ser un email válido" : null;
            case 'numeric':
                return !is_numeric($value) ? "El campo {$field} debe ser numérico" : null;
            default:
                if (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    return strlen($value) < $min ? "El campo {$field} debe tener al menos {$min} caracteres" : null;
                }
                if (strpos($rule, 'max:') === 0) {
                    $max = (int)substr($rule, 4);
                    return strlen($value) > $max ? "El campo {$field} debe tener máximo {$max} caracteres" : null;
                }
                return null;
        }
    }
}
?>
