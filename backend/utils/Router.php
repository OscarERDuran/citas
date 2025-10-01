<?php
/**
 * Router principal
 */

class Router {
    
    private $routes = [];
    private $middlewares = [];
    
    /**
     * Registrar ruta GET
     */
    public function get($path, $handler, $middlewares = []) {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }
    
    /**
     * Registrar ruta POST
     */
    public function post($path, $handler, $middlewares = []) {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }
    
    /**
     * Registrar ruta PUT
     */
    public function put($path, $handler, $middlewares = []) {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }
    
    /**
     * Registrar ruta DELETE
     */
    public function delete($path, $handler, $middlewares = []) {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }
    
    /**
     * Agregar middleware global
     */
    public function middleware($middleware) {
        $this->middlewares[] = $middleware;
    }
    
    /**
     * Agregar ruta
     */
    private function addRoute($method, $path, $handler, $middlewares) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }
    
    /**
     * Ejecutar router
     */
    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remover prefijo de la API si existe
        $path = preg_replace('#^/citas/backend/api#', '', $path);
        $path = rtrim($path, '/') ?: '/';
        
        // Buscar ruta
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                $this->executeRoute($route, $path);
                return;
            }
        }
        
        // Ruta no encontrada
        $this->notFound();
    }
    
    /**
     * Verificar si la ruta coincide
     */
    private function matchRoute($route, $method, $path) {
        if ($route['method'] !== $method) {
            return false;
        }
        
        $routePath = $route['path'];
        
        // Convertir parámetros de ruta a regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $path);
    }
    
    /**
     * Ejecutar ruta
     */
    private function executeRoute($route, $path) {
        try {
            // Ejecutar middlewares globales
            foreach ($this->middlewares as $middleware) {
                $this->executeMiddleware($middleware);
            }
            
            // Ejecutar middlewares específicos de la ruta
            foreach ($route['middlewares'] as $middleware) {
                $this->executeMiddleware($middleware);
            }
            
            // Extraer parámetros de la ruta
            $params = $this->extractParams($route['path'], $path);
            
            // Ejecutar handler
            $handler = $route['handler'];
            
            if (is_string($handler) && strpos($handler, '@') !== false) {
                // Formato "Controller@method"
                [$controllerName, $method] = explode('@', $handler);
                
                if (!class_exists($controllerName)) {
                    throw new Exception("Controller $controllerName no encontrado");
                }
                
                $controller = new $controllerName();
                
                if (!method_exists($controller, $method)) {
                    throw new Exception("Método $method no encontrado en $controllerName");
                }
                
                $controller->$method($params);
                
            } elseif (is_callable($handler)) {
                // Función anónima
                $handler($params);
                
            } else {
                throw new Exception('Handler inválido');
            }
            
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Ejecutar middleware
     */
    private function executeMiddleware($middleware) {
        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                throw new Exception("Middleware $middleware no encontrado");
            }
            
            $middlewareInstance = new $middleware();
            $middlewareInstance->handle();
            
        } elseif (is_callable($middleware)) {
            $middleware();
        }
    }
    
    /**
     * Extraer parámetros de la ruta
     */
    private function extractParams($routePath, $actualPath) {
        $routeParts = explode('/', trim($routePath, '/'));
        $actualParts = explode('/', trim($actualPath, '/'));
        
        $params = [];
        
        for ($i = 0; $i < count($routeParts); $i++) {
            if (isset($routeParts[$i]) && preg_match('/\{([^}]+)\}/', $routeParts[$i], $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = isset($actualParts[$i]) ? $actualParts[$i] : null;
            }
        }
        
        return $params;
    }
    
    /**
     * Manejar ruta no encontrada
     */
    private function notFound() {
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => 'Ruta no encontrada'
        ]);
    }
    
    /**
     * Manejar errores
     */
    private function handleError($exception) {
        error_log('Router Error: ' . $exception->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error interno del servidor',
            'details' => DEBUG_MODE ? $exception->getMessage() : null
        ]);
    }
}
?>
