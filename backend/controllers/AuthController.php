<?php
/**
 * Controlador de Autenticación
 */

class AuthController extends BaseController {
    private $usuarioModel;
    private $jwtHandler;
    
    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->jwtHandler = new JWTHandler();
    }
    
    /**
     * Iniciar sesión
     */
    public function login() {
        $method = $this->validateMethod(['POST']);
        
        // Obtener datos del request
        $data = $this->getJsonInput();
        
        // Validar campos requeridos
        $this->validateRequired($data, ['email', 'password']);
        
        // Sanitizar datos
        $email = $this->sanitizeInput($data['email']);
        $password = $data['password']; // No sanitizar la contraseña
        
        try {
            // Autenticar usuario
            $user = $this->usuarioModel->authenticate($email, $password);
            
            if (!$user) {
                $this->sendError('Credenciales incorrectas', 401);
            }
            
            // Generar token JWT
            $token = $this->jwtHandler->generateToken([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'rol' => $user['rol']
            ]);
            
            // Guardar sesión en base de datos
            $this->saveSession($user['id'], $token);
            
            // Respuesta exitosa
            $this->sendResponse([
                'user' => $user,
                'token' => $token,
                'expires_in' => JWT_EXPIRATION
            ], 200, 'Inicio de sesión exitoso');
            
        } catch (Exception $e) {
            $this->logError('Error en login: ' . $e->getMessage(), ['email' => $email]);
            $this->sendError('Error interno del servidor', 500);
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        $method = $this->validateMethod(['POST']);
        
        try {
            // Obtener token del header
            $token = $this->getTokenFromHeader();
            
            if ($token) {
                // Invalidar sesión en base de datos
                $this->invalidateSession($token);
            }
            
            $this->sendResponse(null, 200, 'Sesión cerrada exitosamente');
            
        } catch (Exception $e) {
            $this->logError('Error en logout: ' . $e->getMessage());
            $this->sendError('Error interno del servidor', 500);
        }
    }
    
    /**
     * Registrar nuevo usuario
     */
    public function register() {
        $method = $this->validateMethod(['POST']);
        
        // Obtener datos del request
        $data = $this->getJsonInput();
        
        // Validar campos requeridos
        $this->validateRequired($data, ['email', 'password', 'nombre']);
        
        // Sanitizar datos
        $data = $this->sanitizeInput($data);
        
        try {
            // Validar datos
            $errors = $this->usuarioModel->validateUser($data);
            
            if (!empty($errors)) {
                $this->sendError('Datos inválidos', 400, $errors);
            }
            
            // Verificar si el email ya existe
            if ($this->usuarioModel->emailExists($data['email'])) {
                $this->sendError('El email ya está registrado', 409);
            }
            
            // Crear usuario
            $userId = $this->usuarioModel->createUser($data);
            
            if ($userId) {
                $user = $this->usuarioModel->getById($userId);
                $this->sendResponse($user, 201, 'Usuario registrado exitosamente');
            } else {
                $this->sendError('Error al crear usuario', 500);
            }
            
        } catch (Exception $e) {
            $this->logError('Error en register: ' . $e->getMessage(), $data);
            $this->sendError('Error interno del servidor', 500);
        }
    }
    
    /**
     * Verificar token
     */
    public function verifyToken() {
        $method = $this->validateMethod(['POST']);
        
        try {
            $token = $this->getTokenFromHeader();
            
            if (!$token) {
                $this->sendError('Token no proporcionado', 401);
            }
            
            // Verificar token JWT
            $payload = $this->jwtHandler->verifyToken($token);
            
            if (!$payload) {
                $this->sendError('Token inválido', 401);
            }
            
            // Verificar si la sesión está activa en BD
            if (!$this->isSessionActive($token)) {
                $this->sendError('Sesión expirada', 401);
            }
            
            // Obtener información actualizada del usuario
            $user = $this->usuarioModel->getById($payload['user_id']);
            
            if (!$user || !$user['activo']) {
                $this->sendError('Usuario inactivo', 401);
            }
            
            $this->sendResponse([
                'user' => $user,
                'token' => $token
            ], 200, 'Token válido');
            
        } catch (Exception $e) {
            $this->logError('Error en verifyToken: ' . $e->getMessage());
            $this->sendError('Token inválido', 401);
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public function changePassword() {
        $method = $this->validateMethod(['POST']);
        
        // Obtener datos del request
        $data = $this->getJsonInput();
        
        // Validar campos requeridos
        $this->validateRequired($data, ['current_password', 'new_password']);
        
        try {
            // Obtener usuario actual
            $user = $this->getCurrentUser();
            
            // Verificar contraseña actual
            if (!$this->usuarioModel->authenticate($user['email'], $data['current_password'])) {
                $this->sendError('Contraseña actual incorrecta', 400);
            }
            
            // Validar nueva contraseña
            if (strlen($data['new_password']) < 6) {
                $this->sendError('La nueva contraseña debe tener al menos 6 caracteres', 400);
            }
            
            // Actualizar contraseña
            $updated = $this->usuarioModel->updatePassword($user['id'], $data['new_password']);
            
            if ($updated) {
                $this->sendResponse(null, 200, 'Contraseña actualizada exitosamente');
            } else {
                $this->sendError('Error al actualizar contraseña', 500);
            }
            
        } catch (Exception $e) {
            $this->logError('Error en changePassword: ' . $e->getMessage());
            $this->sendError('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener token del header Authorization
     */
    private function getTokenFromHeader() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Guardar sesión en base de datos
     */
    private function saveSession($userId, $token) {
        try {
            $sql = "INSERT INTO sesiones (id, usuario_id, token, ip_address, user_agent, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $sessionId = $this->generateToken(16);
            $expiresAt = date('Y-m-d H:i:s', time() + JWT_EXPIRATION);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare($sql);
            
            $stmt->execute([
                $sessionId,
                $userId,
                $token,
                $ipAddress,
                $userAgent,
                $expiresAt
            ]);
            
        } catch (Exception $e) {
            $this->logError('Error al guardar sesión: ' . $e->getMessage());
        }
    }
    
    /**
     * Invalidar sesión
     */
    private function invalidateSession($token) {
        try {
            $sql = "DELETE FROM sesiones WHERE token = ?";
            
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([$token]);
            
        } catch (Exception $e) {
            $this->logError('Error al invalidar sesión: ' . $e->getMessage());
        }
    }
    
    /**
     * Verificar si la sesión está activa
     */
    private function isSessionActive($token) {
        try {
            $sql = "SELECT COUNT(*) as count FROM sesiones WHERE token = ? AND expires_at > NOW()";
            
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([$token]);
            
            return $stmt->fetch()['count'] > 0;
            
        } catch (Exception $e) {
            $this->logError('Error al verificar sesión: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener usuario actual
     */
    private function getCurrentUser() {
        $token = $this->getTokenFromHeader();
        
        if (!$token) {
            $this->sendError('Token no proporcionado', 401);
        }
        
        $payload = $this->jwtHandler->verifyToken($token);
        
        if (!$payload) {
            $this->sendError('Token inválido', 401);
        }
        
        return $this->usuarioModel->getById($payload['user_id']);
    }
}
?>
