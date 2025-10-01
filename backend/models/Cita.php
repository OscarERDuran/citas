<?php
/**
 * Modelo de Cita
 */

class Cita extends BaseModel {
    protected $table = 'citas';
    protected $fillable = [
        'paciente_id', 'medico_id', 'fecha', 'hora', 'estado', 
        'motivo_consulta', 'observaciones', 'costo', 'usuario_registro_id'
    ];
    
    /**
     * Obtener citas con información relacionada
     */
    public function getCitasWithDetails($conditions = [], $orderBy = 'c.fecha DESC, c.hora DESC', $limit = null) {
        try {
            $sql = "SELECT c.*, 
                           p.nombres as paciente_nombres, p.apellidos as paciente_apellidos, 
                           p.documento as paciente_documento, p.telefono as paciente_telefono,
                           m.nombres as medico_nombres, m.apellidos as medico_apellidos,
                           e.nombre as especialidad_nombre,
                           u.nombre as usuario_registro_nombre
                    FROM {$this->table} c
                    LEFT JOIN pacientes p ON c.paciente_id = p.id
                    LEFT JOIN medicos m ON c.medico_id = m.id
                    LEFT JOIN especialidades e ON m.especialidad_id = e.id
                    LEFT JOIN usuarios u ON c.usuario_registro_id = u.id";
            
            if (!empty($conditions)) {
                $whereClause = $this->buildCitasWhereClause($conditions);
                $sql .= " WHERE " . $whereClause;
            }
            
            if ($orderBy) {
                $sql .= " ORDER BY " . $orderBy;
            }
            
            if ($limit) {
                $sql .= " LIMIT " . $limit;
            }
            
            $stmt = $this->db->prepare($sql);
            $this->bindCitasConditions($stmt, $conditions);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en getCitasWithDetails: " . $e->getMessage());
            throw new Exception("Error al obtener citas");
        }
    }
    
    /**
     * Verificar disponibilidad del médico
     */
    public function checkMedicoDisponibilidad($medicoId, $fecha, $hora, $excludeCitaId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE medico_id = ? AND fecha = ? AND hora = ? 
                    AND estado NOT IN ('cancelada', 'no_asistio')";
            
            $params = [$medicoId, $fecha, $hora];
            
            if ($excludeCitaId) {
                $sql .= " AND id != ?";
                $params[] = $excludeCitaId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch()['count'] == 0;
        } catch (PDOException $e) {
            error_log("Error en checkMedicoDisponibilidad: " . $e->getMessage());
            throw new Exception("Error al verificar disponibilidad");
        }
    }
    
    /**
     * Obtener citas del día
     */
    public function getCitasDelDia($fecha = null) {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }
        
        return $this->getCitasWithDetails(['c.fecha' => $fecha], 'c.hora ASC');
    }
    
    /**
     * Obtener próximas citas del paciente
     */
    public function getProximasCitasPaciente($pacienteId, $limit = 5) {
        $conditions = [
            'c.paciente_id' => $pacienteId,
            'c.fecha >=' => date('Y-m-d'),
            'c.estado NOT IN' => ['cancelada', 'no_asistio']
        ];
        
        return $this->getCitasWithDetails($conditions, 'c.fecha ASC, c.hora ASC', $limit);
    }
    
    /**
     * Obtener citas del médico
     */
    public function getCitasMedico($medicoId, $fecha = null, $estado = null) {
        $conditions = ['c.medico_id' => $medicoId];
        
        if ($fecha) {
            $conditions['c.fecha'] = $fecha;
        }
        
        if ($estado) {
            $conditions['c.estado'] = $estado;
        }
        
        return $this->getCitasWithDetails($conditions);
    }
    
    /**
     * Cambiar estado de la cita
     */
    public function cambiarEstado($citaId, $nuevoEstado, $observaciones = null) {
        try {
            $data = ['estado' => $nuevoEstado];
            
            if ($observaciones) {
                $data['observaciones'] = $observaciones;
            }
            
            return $this->update($citaId, $data);
        } catch (Exception $e) {
            throw new Exception("Error al cambiar estado de la cita");
        }
    }
    
    /**
     * Obtener estadísticas de citas
     */
    public function getStats($fechaInicio = null, $fechaFin = null) {
        try {
            $stats = [];
            
            $whereClause = "";
            $params = [];
            
            if ($fechaInicio && $fechaFin) {
                $whereClause = "WHERE fecha BETWEEN ? AND ?";
                $params = [$fechaInicio, $fechaFin];
            }
            
            // Total de citas por estado
            $sql = "SELECT estado, COUNT(*) as count FROM {$this->table} {$whereClause} GROUP BY estado";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['por_estado'] = $stmt->fetchAll();
            
            // Citas por médico
            $sql = "SELECT m.nombres, m.apellidos, COUNT(c.id) as count 
                    FROM {$this->table} c
                    JOIN medicos m ON c.medico_id = m.id
                    {$whereClause}
                    GROUP BY c.medico_id
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['por_medico'] = $stmt->fetchAll();
            
            // Citas por especialidad
            $sql = "SELECT e.nombre, COUNT(c.id) as count 
                    FROM {$this->table} c
                    JOIN medicos m ON c.medico_id = m.id
                    JOIN especialidades e ON m.especialidad_id = e.id
                    {$whereClause}
                    GROUP BY e.id
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['por_especialidad'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error en getStats: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas");
        }
    }
    
    /**
     * Construir clausula WHERE para citas
     */
    private function buildCitasWhereClause($conditions) {
        $clauses = [];
        foreach ($conditions as $field => $value) {
            if (strpos($field, ' >=') !== false) {
                $field = str_replace(' >=', '', $field);
                $clauses[] = "{$field} >= ?";
            } elseif (strpos($field, ' NOT IN') !== false) {
                $field = str_replace(' NOT IN', '', $field);
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $clauses[] = "{$field} NOT IN ({$placeholders})";
            } elseif (is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $clauses[] = "{$field} IN ({$placeholders})";
            } else {
                $clauses[] = "{$field} = ?";
            }
        }
        return implode(' AND ', $clauses);
    }
    
    /**
     * Vincular condiciones para citas
     */
    private function bindCitasConditions($stmt, $conditions) {
        $paramIndex = 1;
        foreach ($conditions as $field => $value) {
            if (strpos($field, ' >=') !== false || !is_array($value)) {
                if (!is_array($value)) {
                    $stmt->bindValue($paramIndex++, $value);
                }
            } else {
                foreach ($value as $subValue) {
                    $stmt->bindValue($paramIndex++, $subValue);
                }
            }
        }
    }
    
    /**
     * Validar datos de cita
     */
    public function validateCita($data) {
        $rules = [
            'paciente_id' => ['required', 'numeric'],
            'medico_id' => ['required', 'numeric'],
            'fecha' => ['required'],
            'hora' => ['required'],
        ];
        
        return $this->validate($data, $rules);
    }
}
?>
