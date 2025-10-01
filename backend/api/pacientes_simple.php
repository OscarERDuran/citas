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

// Archivo para almacenar pacientes (simulando base de datos)
$pacientesFile = __DIR__ . '/../data/pacientes.json';

// Crear directorios si no existen
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

try {
    // Obtener datos del cuerpo de la petición
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Log para debug
    error_log("Datos recibidos para paciente: " . print_r($data, true));
    
    // Validar datos requeridos
    $requiredFields = ['nombres', 'apellidos', 'documento', 'tipoDocumento', 'email', 'telefono', 'fechaNacimiento'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            exit;
        }
    }
    
    // Leer pacientes existentes
    $pacientes = [];
    if (file_exists($pacientesFile)) {
        $pacientes = json_decode(file_get_contents($pacientesFile), true) ?: [];
    }
    
    // Verificar si el documento ya existe
    foreach ($pacientes as $paciente) {
        if ($paciente['documento'] == $data['documento'] && 
            $paciente['tipoDocumento'] == $data['tipoDocumento']) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Ya existe un paciente con este documento']);
            exit;
        }
    }
    
    // Verificar si el email ya existe
    foreach ($pacientes as $paciente) {
        if ($paciente['email'] == $data['email']) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Ya existe un paciente con este email']);
            exit;
        }
    }
    
    // Crear nuevo paciente
    $nuevoPaciente = [
        'id' => count($pacientes) + 1,
        'nombre' => $data['nombre'],
        'apellido' => $data['apellido'],
        'documento' => $data['documento'],
        'tipoDocumento' => $data['tipoDocumento'],
        'email' => $data['email'],
        'telefono' => $data['telefono'],
        'fechaNacimiento' => $data['fechaNacimiento'],
        'direccion' => $data['direccion'] ?? '',
        'genero' => $data['genero'] ?? '',
        'estadoCivil' => $data['estadoCivil'] ?? '',
        'contactoEmergencia' => $data['contactoEmergencia'] ?? '',
        'telefonoEmergencia' => $data['telefonoEmergencia'] ?? '',
        'alergias' => $data['alergias'] ?? '',
        'medicamentos' => $data['medicamentos'] ?? '',
        'enfermedades' => $data['enfermedades'] ?? '',
        'observaciones' => $data['observaciones'] ?? '',
        'activo' => true,
        'fecha_registro' => date('Y-m-d H:i:s')
    ];
    
    // Agregar paciente al array
    $pacientes[] = $nuevoPaciente;
    
    // Guardar en archivo
    file_put_contents($pacientesFile, json_encode($pacientes, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'message' => 'Paciente registrado exitosamente',
        'paciente' => $nuevoPaciente
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'details' => $e->getMessage()
    ]);
}
?>