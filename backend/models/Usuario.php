<?php
/**
 * Modelo de Usuario
 */

class Usuario extends BaseModel {
    protected $table = 'usuarios';
    protected $fillable = ['email', 'password', 'nombre', 'rol', 'activo'];
    protected $hidden = ['password'];
    
    /**
     * Autenticar usuario
     */
    public function authenticate($email, $password) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE email = ? AND activo = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Actualizar último acceso
                $this->updateLastAccess($user['id']);
                
                // Ocultar contraseña
                unset($user['password']);
                return $user;
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Error en authenticate: " . $e->getMessage());
            throw new Exception("Error de autenticación");
        }
    }
    
    /**
     * Crear usuario con contraseña hasheada
     */
    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
    }
    
    /**
     * Actualizar contraseña
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword]);
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
     * Actualizar último acceso
     */
    private function updateLastAccess($userId) {
        try {
            $sql = "UPDATE {$this->table} SET ultimo_acceso = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error al actualizar último acceso: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener usuarios por rol
     */
    public function getByRole($role) {
        return $this->getAll(['rol' => $role]);
    }
    
    /**
     * Validar datos de usuario
     */
    public function validateUser($data, $isUpdate = false) {
        $rules = [
            'email' => ['required', 'email'],
            'nombre' => ['required', 'min:2', 'max:100'],
            'rol' => ['required']
        ];
        
        if (!$isUpdate) {
            $rules['password'] = ['required', 'min:6'];
        }
        
        return $this->validate($data, $rules);
    }
}
?>
