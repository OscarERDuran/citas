<?php
/**
 * Modelo de Especialidad
 */

class Especialidad extends BaseModel {
    protected $table = 'especialidades';
    protected $fillable = ['nombre', 'descripcion', 'activo'];
    
    /**
     * Obtener especialidades con conteo de médicos
     */
    public function getWithMedicosCount() {
        try {
            $sql = "SELECT e.*, COUNT(m.id) as total_medicos, 
                           COUNT(CASE WHEN m.activo = 1 THEN 1 END) as medicos_activos
                    FROM {$this->table} e
                    LEFT JOIN medicos m ON e.id = m.especialidad_id
                    WHERE e.activo = 1
                    GROUP BY e.id
                    ORDER BY e.nombre";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en getWithMedicosCount: " . $e->getMessage());
            throw new Exception("Error al obtener especialidades");
        }
    }
    
    /**
     * Obtener especialidades activas
     */
    public function getActivas() {
        return $this->getAll(['activo' => 1], 'nombre ASC');
    }
    
    /**
     * Verificar si el nombre existe
     */
    public function nombreExists($nombre, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE nombre = ?";
            $params = [$nombre];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch()['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error en nombreExists: " . $e->getMessage());
            throw new Exception("Error al verificar nombre");
        }
    }
    
    /**
     * Obtener estadísticas de especialidades
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Especialidades más solicitadas (por citas)
            $sql = "SELECT e.nombre, COUNT(c.id) as total_citas
                    FROM {$this->table} e
                    LEFT JOIN medicos m ON e.id = m.especialidad_id
                    LEFT JOIN citas c ON m.id = c.medico_id
                    WHERE e.activo = 1
                    GROUP BY e.id
                    ORDER BY total_citas DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['mas_solicitadas'] = $stmt->fetchAll();
            
            // Especialidades con más médicos
            $sql = "SELECT e.nombre, COUNT(m.id) as total_medicos
                    FROM {$this->table} e
                    LEFT JOIN medicos m ON e.id = m.especialidad_id AND m.activo = 1
                    WHERE e.activo = 1
                    GROUP BY e.id
                    ORDER BY total_medicos DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['con_mas_medicos'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error en getStats: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Validar datos de especialidad
     */
    public function validateEspecialidad($data) {
        $rules = [
            'nombre' => ['required', 'min:2', 'max:100']
        ];
        
        return $this->validate($data, $rules);
    }
}
?>
