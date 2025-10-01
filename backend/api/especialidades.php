<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Datos hardcodeados temporalmente
    $especialidades = [
        [
            'id' => 1,
            'nombre' => 'Ginecología',
            'descripcion' => 'Especialidad médica dedicada al cuidado del sistema reproductivo femenino'
        ],
        [
            'id' => 2,
            'nombre' => 'Cardiología',
            'descripcion' => 'Especialidad médica que se ocupa de las afecciones del corazón'
        ],
        [
            'id' => 3,
            'nombre' => 'Pediatría',
            'descripcion' => 'Especialidad médica que estudia al niño y sus enfermedades'
        ],
        [
            'id' => 4,
            'nombre' => 'Medicina General',
            'descripcion' => 'Atención médica integral y continua'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'especialidades' => $especialidades
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
