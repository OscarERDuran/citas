<?php
/**
 * Controlador Base - Funcionalidades comunes
 */

abstract class BaseController {
    
    /**
     * Enviar respuesta JSON
     */
    protected function sendResponse($data, $statusCode = 200, $message = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'data' => $data,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Enviar respuesta de error
     */
    protected function sendError($message, $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Validar método HTTP
     */
    protected function validateMethod($allowedMethods) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, $allowedMethods)) {
            $this->sendError('Método no permitido', 405);
        }
        
        return $method;
    }
    
    /**
     * Obtener datos JSON del request
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('JSON inválido', 400);
        }
        
        return $data ?: [];
    }
    
    /**
     * Sanitizar datos de entrada
     */
    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar campos requeridos
     */
    protected function validateRequired($data, $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('Campos requeridos faltantes: ' . implode(', ', $missing), 400);
        }
    }
    
    /**
     * Aplicar paginación
     */
    protected function paginate($page = 1, $pageSize = null) {
        if (!$pageSize) {
            $pageSize = DEFAULT_PAGE_SIZE;
        }
        
        $page = max(1, (int)$page);
        $pageSize = min(MAX_PAGE_SIZE, max(1, (int)$pageSize));
        
        $offset = ($page - 1) * $pageSize;
        
        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'offset' => $offset
        ];
    }
    
    /**
     * Construir respuesta paginada
     */
    protected function buildPaginatedResponse($data, $total, $page, $pageSize) {
        $totalPages = ceil($total / $pageSize);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $pageSize,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    }
    
    /**
     * Logging de errores
     */
    protected function logError($message, $context = []) {
        $logMessage = date('[Y-m-d H:i:s] ') . $message;
        
        if (!empty($context)) {
            $logMessage .= ' Context: ' . json_encode($context);
        }
        
        error_log($logMessage);
    }
    
    /**
     * Validar formato de fecha
     */
    protected function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validar formato de hora
     */
    protected function validateTime($time, $format = 'H:i') {
        $t = DateTime::createFromFormat($format, $time);
        return $t && $t->format($format) === $time;
    }
    
    /**
     * Generar token único
     */
    protected function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
?>
