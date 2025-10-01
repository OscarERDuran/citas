<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos de entrada
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Email y password requeridos']);
        exit;
    }
    
    $email = $input['email'];
    $password = $input['password'];
    
    // Conectar a la base de datos
    require_once __DIR__ . '/../config/DatabaseMySQLi.php';
    
    $db = new Database();
    $connection = $db->getConnection();
    
    // Buscar usuario
    $email_escaped = $connection->real_escape_string($email);
    $query = "SELECT u.*, 
                     CASE 
                         WHEN u.rol = 'medico' THEN m.id
                         WHEN u.rol = 'paciente' THEN p.id
                         ELSE NULL
                     END as profile_id
              FROM usuarios u
              LEFT JOIN medicos m ON u.id = m.usuario_id AND u.rol = 'medico'
              LEFT JOIN pacientes p ON u.id = p.usuario_id AND u.rol = 'paciente'
              WHERE u.email = '$email_escaped' AND u.activo = 1";
    
    $result = $connection->query($query);
    
    if (!$result || $result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => 'Credenciales inválidas']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Verificar password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => 'Credenciales inválidas']);
        exit;
    }
    
    // Generar token simple (en producción usar JWT real)
    $token = base64_encode(json_encode([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'exp' => time() + 3600 // 1 hora
    ]));
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Login exitoso',
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'apellido' => $user['apellido'],
            'email' => $user['email'],
            'rol' => $user['rol'],
            'profile_id' => $user['profile_id']
        ],
        'token' => $token
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error interno del servidor',
        'details' => $e->getMessage()
    ]);
}
?>
