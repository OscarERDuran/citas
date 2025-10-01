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
    // Obtener datos del cuerpo de la petición
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Log para debug
    error_log("=== REGISTRAR PACIENTE BD ===");
    error_log("JSON recibido: " . $json);
    error_log("Datos decodificados: " . print_r($data, true));
    
    // Mapear nombres de campos del frontend a la BD
    $mappedData = [
        'nombres' => $data['nombres'] ?? '',
        'apellidos' => $data['apellidos'] ?? '',
        'documento' => $data['documento'] ?? '',
        'tipo_documento' => $data['tipoDocumento'] ?? '',
        'email' => $data['email'] ?? '',
        'telefono' => $data['telefono'] ?? '',
        'fecha_nacimiento' => $data['fechaNacimiento'] ?? '',
        'genero' => $data['genero'] ?? '',
        'direccion' => $data['direccion'] ?? '',
        'ciudad' => $data['ciudad'] ?? '',
        'departamento' => $data['departamento'] ?? '',
        'eps' => $data['eps'] ?? '',
        'tipo_sangre' => $data['tipoSangre'] ?? '',
        'alergias' => $data['alergias'] ?? '',
        'observaciones_medicas' => $data['observaciones'] ?? ''
    ];
    
    // Validar datos requeridos
    $requiredFields = ['nombres', 'apellidos', 'documento', 'tipo_documento', 'email', 'telefono', 'fecha_nacimiento'];
    foreach ($requiredFields as $field) {
        if (empty($mappedData[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            error_log("Campo faltante: $field - Datos mapeados: " . print_r($mappedData, true));
            exit;
        }
    }
    
    // Conectar a la base de datos
    require_once __DIR__ . '/../config/DatabaseMySQLi.php';
    
    $db = new Database();
    $connection = $db->getConnection();
    
    // Verificar si el documento ya existe
    $checkDocQuery = "SELECT id FROM pacientes WHERE documento = ? AND tipo_documento = ?";
    $stmt = $connection->prepare($checkDocQuery);
    $stmt->bind_param("ss", $mappedData['documento'], $mappedData['tipo_documento']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Ya existe un paciente con este documento']);
        $connection->close();
        exit;
    }
    
    // Verificar si el email ya existe
    $checkEmailQuery = "SELECT id FROM pacientes WHERE email = ?";
    $stmt = $connection->prepare($checkEmailQuery);
    $stmt->bind_param("s", $mappedData['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Ya existe un paciente con este email']);
        $connection->close();
        exit;
    }
    
    // Insertar nuevo paciente (usando la estructura real de la tabla)
    $insertQuery = "INSERT INTO pacientes (
        nombres, apellidos, documento, tipo_documento, email, telefono, 
        fecha_nacimiento, genero, direccion, ciudad, departamento,
        eps, tipo_sangre, alergias, observaciones_medicas, activo, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    
    $stmt = $connection->prepare($insertQuery);
    
    if (!$stmt) {
        error_log("Error preparando consulta: " . $connection->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $connection->error]);
        $connection->close();
        exit;
    }
    
    // Usar datos mapeados directamente
    $stmt->bind_param("sssssssssssssss", 
        $mappedData['nombres'], 
        $mappedData['apellidos'], 
        $mappedData['documento'], 
        $mappedData['tipo_documento'], 
        $mappedData['email'], 
        $mappedData['telefono'],
        $mappedData['fecha_nacimiento'], 
        $mappedData['genero'], 
        $mappedData['direccion'], 
        $mappedData['ciudad'], 
        $mappedData['departamento'],
        $mappedData['eps'], 
        $mappedData['tipo_sangre'], 
        $mappedData['alergias'], 
        $mappedData['observaciones_medicas']
    );
    
    if ($stmt->execute()) {
        $pacienteId = $connection->insert_id;
        error_log("Paciente creado con ID: " . $pacienteId);
        error_log("Filas afectadas: " . $connection->affected_rows);
        
        echo json_encode([
            'success' => true,
            'message' => 'Paciente registrado exitosamente',
            'paciente' => [
                'id' => $pacienteId,
                'nombres' => $mappedData['nombres'],
                'apellidos' => $mappedData['apellidos'],
                'documento' => $mappedData['documento'],
                'email' => $mappedData['email']
            ]
        ]);
    } else {
        error_log("Error ejecutando consulta: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error ejecutando consulta: ' . $stmt->error]);
    }
    
    $connection->close();
    
} catch (Exception $e) {
    error_log("Error en registro de paciente: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'details' => $e->getMessage()
    ]);
}
?>