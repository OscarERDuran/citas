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
    
    // Verificación simple con usuarios hardcodeados para prueba inicial
    $valid_users = [
        'admin@famicitas.com' => [
            'password' => 'admin123',
            'id' => 1,
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'rol' => 'admin',
            'profile_id' => 1  // Para pacientes, este sería el ID del paciente
        ],
        'recepcion@famicitas.com' => [
            'password' => 'recepcion123',
            'id' => 2,
            'nombre' => 'Recepcionista',
            'apellido' => 'Sistema',
            'rol' => 'recepcionista',
            'profile_id' => null
        ]
    ];
    
    // Verificar usuario
    if (!isset($valid_users[$email])) {
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    $user_data = $valid_users[$email];
    
    // Verificar contraseña (simple comparación por ahora)
    if ($password !== $user_data['password']) {
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => 'Contraseña incorrecta']);
        exit;
    }
    
    // Generar token simple
    $token = base64_encode(json_encode([
        'user_id' => $user_data['id'],
        'email' => $email,
        'rol' => $user_data['rol'],
        'exp' => time() + 3600 // 1 hora
    ]));
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Login exitoso',
        'user' => [
            'id' => $user_data['id'],
            'nombre' => $user_data['nombre'],
            'apellido' => $user_data['apellido'],
            'email' => $email,
            'rol' => $user_data['rol'],
            'profile_id' => $user_data['profile_id']
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