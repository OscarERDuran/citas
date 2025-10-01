<?php
/**
 * Modelo de Paciente
 */

class Paciente extends BaseModel {
    protected $table = 'pacientes';
    protected $fillable = [
        'nombres', 'apellidos', 'tipo_documento', 'documento', 
        'fecha_nacimiento', 'genero', 'telefono', 'email', 
        'direccion', 'ciudad', 'departamento', 'eps', 
        'tipo_sangre', 'alergias', 'observaciones_medicas', 'activo'
    ];
    
    /**
     * Buscar pacientes
     */
    public function search($term, $limit = 10) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE (nombres LIKE ? OR apellidos LIKE ? OR documento LIKE ? OR email LIKE ?) 
                    AND activo = 1 
                    ORDER BY nombres, apellidos 
                    LIMIT ?";
            
            $searchTerm = '%' . $term . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en search: " . $e->getMessage());
            throw new Exception("Error al buscar pacientes");
        }
    }
    
    /**
     * Obtener paciente por documento
     */
    public function getByDocument($documento) {
        return $this->getAll(['documento' => $documento, 'activo' => 1]);
    }
    
    /**
     * Verificar si el documento existe
     */
    public function documentExists($documento, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE documento = ?";
            $params = [$documento];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch()['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error en documentExists: " . $e->getMessage());
            throw new Exception("Error al verificar documento");
        }
    }
    
    /**
     * Obtener estadísticas de pacientes
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total de pacientes activos
            $stats['total_activos'] = $this->count(['activo' => 1]);
            
            // Pacientes por género
            $sql = "SELECT genero, COUNT(*) as count FROM {$this->table} WHERE activo = 1 GROUP BY genero";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['por_genero'] = $stmt->fetchAll();
            
            // Pacientes por rango de edad
            $sql = "SELECT 
                        CASE 
                            WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) < 18 THEN 'Menor de 18'
                            WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 18 AND 30 THEN '18-30 años'
                            WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 31 AND 50 THEN '31-50 años'
                            WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 51 AND 70 THEN '51-70 años'
                            ELSE 'Mayor de 70'
                        END as rango_edad,
                        COUNT(*) as count
                    FROM {$this->table} 
                    WHERE activo = 1 
                    GROUP BY rango_edad";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['por_edad'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error en getStats: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Obtener historial de citas del paciente
     */
    public function getHistorialCitas($pacienteId, $limit = 10) {
        try {
            $sql = "SELECT c.*, m.nombres as medico_nombres, m.apellidos as medico_apellidos, 
                           e.nombre as especialidad_nombre
                    FROM citas c
                    JOIN medicos m ON c.medico_id = m.id
                    JOIN especialidades e ON m.especialidad_id = e.id
                    WHERE c.paciente_id = ?
                    ORDER BY c.fecha DESC, c.hora DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pacienteId, $limit]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en getHistorialCitas: " . $e->getMessage());
            throw new Exception("Error al obtener historial de citas");
        }
    }
    
    /**
     * Validar datos de paciente
     */
    public function validatePaciente($data) {
        $rules = [
            'nombres' => ['required', 'min:2', 'max:100'],
            'apellidos' => ['required', 'min:2', 'max:100'],
            'tipo_documento' => ['required'],
            'documento' => ['required', 'min:6', 'max:20'],
            'fecha_nacimiento' => ['required'],
            'genero' => ['required'],
            'telefono' => ['required', 'min:7', 'max:20'],
            'direccion' => ['required', 'min:5'],
            'ciudad' => ['required', 'min:2', 'max:100'],
            'departamento' => ['required']
        ];
        
        if (!empty($data['email'])) {
            $rules['email'] = ['email'];
        }
        
        return $this->validate($data, $rules);
    }
    
    /**
     * Calcular edad
     */
    public function calculateAge($fechaNacimiento) {
        $birthDate = new DateTime($fechaNacimiento);
        $today = new DateTime('today');
        return $birthDate->diff($today)->y;
    }
}
?>
