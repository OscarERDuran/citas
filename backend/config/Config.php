<?php
/**
 * Configuración General de la Aplicación
 */

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.use_strict_mode', 1);

// Constantes de la aplicación
define('BASE_URL', 'http://localhost/citas/');
define('API_BASE_URL', 'http://localhost/citas/backend/api/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('LOG_DIR', __DIR__ . '/../logs/');

// Configuración de base de datos
define('DB_HOST', '193.203.166.24');
define('DB_NAME', 'u573706850_famicitas');
define('DB_USER', 'u573706850_famicitas');
define('DB_PASS', 'Famicitas123');
define('DB_PORT', 3306);

// Configuración de JWT
define('JWT_SECRET', 'famicitas_secret_key_2025');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 3600); // 1 hora

// Configuración de paginación
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// Configuración de email (para futuras implementaciones)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Headers CORS (solo en contexto web)
if (isset($_SERVER['REQUEST_METHOD'])) {
    header('Access-Control-Allow-Origin: ' . BASE_URL);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    // Manejo de preflight OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Autoloader simple
spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../middleware/',
        __DIR__ . '/../utils/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Función para crear directorios si no existen
function createDirectoryIfNotExists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Crear directorios necesarios
createDirectoryIfNotExists(LOG_DIR);
createDirectoryIfNotExists(UPLOAD_DIR);
?>
