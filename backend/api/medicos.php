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
    // Parámetros opcionales
    $especialidad_id = isset($_GET['especialidad_id']) ? (int)$_GET['especialidad_id'] : null;
    
    // Datos hardcodeados temporalmente
    $todos_medicos = [
        [
            'id' => 1,
            'nombre' => 'María',
            'apellido' => 'González',
            'nombre_completo' => 'María González',
            'cedula' => '12345678',
            'telefono' => '555-0001',
            'especialidad' => 'Ginecología',
            'especialidad_id' => 1,
            'numero_licencia' => 'LIC001',
            'biografia' => 'Especialista en ginecología con 10 años de experiencia'
        ],
        [
            'id' => 2,
            'nombre' => 'Carlos',
            'apellido' => 'Rodríguez',
            'nombre_completo' => 'Carlos Rodríguez',
            'cedula' => '87654321',
            'telefono' => '555-0002',
            'especialidad' => 'Cardiología',
            'especialidad_id' => 2,
            'numero_licencia' => 'LIC002',
            'biografia' => 'Cardiólogo especializado en cirugía cardiovascular'
        ],
        [
            'id' => 3,
            'nombre' => 'Ana',
            'apellido' => 'Martínez',
            'nombre_completo' => 'Ana Martínez',
            'cedula' => '11223344',
            'telefono' => '555-0003',
            'especialidad' => 'Pediatría',
            'especialidad_id' => 3,
            'numero_licencia' => 'LIC003',
            'biografia' => 'Pediatra con especialización en neonatología'
        ],
        [
            'id' => 4,
            'nombre' => 'Luis',
            'apellido' => 'Pérez',
            'nombre_completo' => 'Luis Pérez',
            'cedula' => '44332211',
            'telefono' => '555-0004',
            'especialidad' => 'Medicina General',
            'especialidad_id' => 4,
            'numero_licencia' => 'LIC004',
            'biografia' => 'Médico general con amplia experiencia en atención primaria'
        ]
    ];
    
    // Filtrar por especialidad si se especifica
    $medicos = [];
    foreach ($todos_medicos as $medico) {
        if (!$especialidad_id || $medico['especialidad_id'] == $especialidad_id) {
            $medicos[] = $medico;
        }
    }
    
    echo json_encode([
        'success' => true,
        'medicos' => $medicos
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
