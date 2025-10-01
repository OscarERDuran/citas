<?php
require_once __DIR__ . '/../utils/JWTHandler.php';

/**
 * Middleware de Autenticación
 */

class AuthMiddleware {
    
    private $jwtHandler;
    
    public function __construct() {
        $this->jwtHandler = new JWTHandler();
    }
    
    /**
     * Verificar autenticación
     */
    public function authenticate() {
        $headers = $this->getAuthorizationHeader();
        
        if (!$headers) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Token de acceso requerido'
            ]);
            exit;
        }
        
        $token = str_replace('Bearer ', '', $headers);
        $payload = $this->jwtHandler->verifyToken($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Token inválido o expirado'
            ]);
            exit;
        }
        
        // Verificar si el usuario sigue activo en la base de datos
        $usuario = new Usuario();
        $user = $usuario->buscarPorId($payload['user_id']);
        
        if (!$user || !$user['activo']) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Usuario no autorizado'
            ]);
            exit;
        }
        
        // Agregar información del usuario a la request global
        $GLOBALS['current_user'] = $user;
        $GLOBALS['current_user_payload'] = $payload;
        
        return true;
    }
    
    /**
     * Verificar rol específico
     */
    public function requireRole($requiredRole) {
        $this->authenticate();
        
        $user = $GLOBALS['current_user'];
        
        if ($user['rol'] !== $requiredRole) {
            http_response_code(403);
            echo json_encode([
                'error' => true,
                'message' => 'Permisos insuficientes'
            ]);
            exit;
        }
        
        return true;
    }
    
    /**
     * Verificar múltiples roles
     */
    public function requireAnyRole($allowedRoles) {
        $this->authenticate();
        
        $user = $GLOBALS['current_user'];
        
        if (!in_array($user['rol'], $allowedRoles)) {
            http_response_code(403);
            echo json_encode([
                'error' => true,
                'message' => 'Permisos insuficientes'
            ]);
            exit;
        }
        
        return true;
    }
    
    /**
     * Obtener header de autorización
     */
    private function getAuthorizationHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    /**
     * Middleware opcional (no falla si no hay token)
     */
    public function optionalAuth() {
        $headers = $this->getAuthorizationHeader();
        
        if ($headers) {
            $token = str_replace('Bearer ', '', $headers);
            $payload = $this->jwtHandler->verifyToken($token);
            
            if ($payload) {
                $usuario = new Usuario();
                $user = $usuario->buscarPorId($payload['user_id']);
                
                if ($user && $user['activo']) {
                    $GLOBALS['current_user'] = $user;
                    $GLOBALS['current_user_payload'] = $payload;
                }
            }
        }
        
        return true;
    }
}
?>
