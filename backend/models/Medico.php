<?php
/**
 * Modelo de Médico
 */

class Medico extends BaseModel {
    protected $table = 'medicos';
    protected $fillable = [
        'nombres', 'apellidos', 'documento', 'telefono', 'email',
        'especialidad_id', 'numero_licencia', 'horario_inicio', 
        'horario_fin', 'activo'
    ];
    
    /**
     * Obtener médicos con especialidad
     */
    public function getMedicosWithEspecialidad($conditions = []) {
        try {
            $sql = "SELECT m.*, e.nombre as especialidad_nombre, e.descripcion as especialidad_descripcion
                    FROM {$this->table} m
                    LEFT JOIN especialidades e ON m.especialidad_id = e.id";
            
            if (!empty($conditions)) {
                $whereClause = $this->buildWhereClause($conditions);
                $sql .= " WHERE " . $whereClause;
            }
            
            $sql .= " ORDER BY m.nombres, m.apellidos";
            
            $stmt = $this->db->prepare($sql);
            $this->bindConditions($stmt, $conditions);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en getMedicosWithEspecialidad: " . $e->getMessage());
            throw new Exception("Error al obtener médicos");
        }
    }
    
    /**
     * Obtener médicos por especialidad
     */
    public function getByEspecialidad($especialidadId) {
        return $this->getMedicosWithEspecialidad(['m.especialidad_id' => $especialidadId, 'm.activo' => 1]);
    }
    
    /**
     * Verificar disponibilidad del médico en un horario
     */
    public function checkDisponibilidad($medicoId, $fecha, $hora) {
        try {
            // Verificar horario de trabajo
            $medico = $this->getById($medicoId);
            if (!$medico) {
                return false;
            }
            
            $horaConsulta = new DateTime($hora);
            $horaInicio = new DateTime($medico['horario_inicio']);
            $horaFin = new DateTime($medico['horario_fin']);
            
            // Verificar si está dentro del horario de trabajo
            if ($horaConsulta < $horaInicio || $horaConsulta >= $horaFin) {
                return false;
            }
            
            // Verificar si ya tiene cita en ese horario
            $citaModel = new Cita();
            return $citaModel->checkMedicoDisponibilidad($medicoId, $fecha, $hora);
            
        } catch (Exception $e) {
            error_log("Error en checkDisponibilidad: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener horarios disponibles del médico
     */
    public function getHorariosDisponibles($medicoId, $fecha) {
        try {
            $medico = $this->getById($medicoId);
            if (!$medico) {
                return [];
            }
            
            $horariosDisponibles = [];
            $horaInicio = new DateTime($medico['horario_inicio']);
            $horaFin = new DateTime($medico['horario_fin']);
            
            // Generar horarios cada 30 minutos
            $current = clone $horaInicio;
            while ($current < $horaFin) {
                $horaStr = $current->format('H:i');
                
                // Verificar disponibilidad
                if ($this->checkDisponibilidad($medicoId, $fecha, $horaStr . ':00')) {
                    $horariosDisponibles[] = $horaStr;
                }
                
                $current->add(new DateInterval('PT30M')); // Agregar 30 minutos
            }
            
            return $horariosDisponibles;
        } catch (Exception $e) {
            error_log("Error en getHorariosDisponibles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener agenda del médico
     */
    public function getAgenda($medicoId, $fechaInicio, $fechaFin) {
        try {
            $citaModel = new Cita();
            $conditions = [
                'c.medico_id' => $medicoId,
                'c.fecha >=' => $fechaInicio,
                'c.fecha <=' => $fechaFin
            ];
            
            return $citaModel->getCitasWithDetails($conditions, 'c.fecha ASC, c.hora ASC');
        } catch (Exception $e) {
            error_log("Error en getAgenda: " . $e->getMessage());
            throw new Exception("Error al obtener agenda");
        }
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
     * Verificar si el email existe
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
            $params = [$email];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch()['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error en emailExists: " . $e->getMessage());
            throw new Exception("Error al verificar email");
        }
    }
    
    /**
     * Obtener estadísticas del médico
     */
    public function getStats($medicoId, $fechaInicio = null, $fechaFin = null) {
        try {
            $stats = [];
            
            $whereClause = "WHERE c.medico_id = ?";
            $params = [$medicoId];
            
            if ($fechaInicio && $fechaFin) {
                $whereClause .= " AND c.fecha BETWEEN ? AND ?";
                $params[] = $fechaInicio;
                $params[] = $fechaFin;
            }
            
            // Total de citas por estado
            $sql = "SELECT c.estado, COUNT(*) as count 
                    FROM citas c 
                    {$whereClause} 
                    GROUP BY c.estado";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['por_estado'] = $stmt->fetchAll();
            
            // Promedio de citas por día
            $sql = "SELECT AVG(citas_por_dia) as promedio
                    FROM (
                        SELECT COUNT(*) as citas_por_dia 
                        FROM citas c 
                        {$whereClause} 
                        GROUP BY c.fecha
                    ) as subquery";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            $stats['promedio_citas_dia'] = round($result['promedio'] ?? 0, 2);
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error en getStats: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Validar datos de médico
     */
    public function validateMedico($data) {
        $rules = [
            'nombres' => ['required', 'min:2', 'max:100'],
            'apellidos' => ['required', 'min:2', 'max:100'],
            'documento' => ['required', 'min:6', 'max:20'],
            'telefono' => ['required', 'min:7', 'max:20'],
            'especialidad_id' => ['required', 'numeric'],
            'numero_licencia' => ['required', 'min:3', 'max:50']
        ];
        
        if (!empty($data['email'])) {
            $rules['email'] = ['email'];
        }
        
        return $this->validate($data, $rules);
    }
}
?>
