<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

try {
    // Intentar conexión a la base de datos
    require_once __DIR__ . '/../config/DatabaseMySQLi.php';
    
    $db = new Database();
    $connection = $db->getConnection();
    
    // Probar una consulta simple
    $result = $connection->query("SELECT COUNT(*) as count FROM usuarios");
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'status' => 'ok',
        'database' => 'connected',
        'users_count' => $row['count'],
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'database' => 'disconnected',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
