<?php
/**
 * Middleware CORS
 */

class CorsMiddleware {
    
    /**
     * Configurar CORS
     */
    public static function handle() {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8080',
            'https://localhost:3000',
            'https://localhost:8080'
        ];
        
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Verificar origen permitido
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header("Access-Control-Allow-Origin: *");
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400"); // 24 horas
        
        // Manejar preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Middleware para APIs
     */
    public static function apiHeaders() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        self::handle();
    }
}
?>
