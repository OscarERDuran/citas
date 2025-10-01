<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Manejar diferentes métodos HTTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleCreateCita();
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetCitas();
    exit;
} else {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Método no permitido']);
    exit;
}

function handleCreateCita() {
    try {
        // Verificar autenticación
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token requerido']);
            return;
        }
        
        // Obtener datos del cuerpo de la petición
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Validar datos requeridos
        if (!isset($data['paciente_id']) || !isset($data['medico_id']) || 
            !isset($data['especialidad_id']) || !isset($data['fecha_cita']) || 
            !isset($data['hora_cita']) || !isset($data['motivo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        // Conectar a la base de datos
        require_once __DIR__ . '/../config/DatabaseMySQLi.php';
        
        $db = new Database();
        $connection = $db->getConnection();
        
        // Verificar que el médico existe y pertenece a la especialidad
        $checkQuery = "SELECT m.id FROM medicos m 
                       WHERE m.id = ? AND m.especialidad_id = ?";
        $stmt = $connection->prepare($checkQuery);
        $stmt->bind_param("ii", $data['medico_id'], $data['especialidad_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Médico no válido para esta especialidad']);
            return;
        }
        
        // Verificar disponibilidad (no hay otra cita en la misma fecha/hora con el mismo médico)
        $availabilityQuery = "SELECT id FROM citas 
                             WHERE medico_id = ? AND fecha_cita = ? AND hora_cita = ? 
                             AND estado IN ('programada', 'confirmada')";
        $stmt = $connection->prepare($availabilityQuery);
        $stmt->bind_param("iss", $data['medico_id'], $data['fecha_cita'], $data['hora_cita']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'El médico no está disponible en esa fecha y hora']);
            return;
        }
        
        // Insertar nueva cita
        $insertQuery = "INSERT INTO citas (paciente_id, medico_id, fecha_cita, hora_cita, motivo, estado, fecha_creacion) 
                        VALUES (?, ?, ?, ?, ?, 'programada', NOW())";
        $stmt = $connection->prepare($insertQuery);
        $stmt->bind_param("iisss", 
            $data['paciente_id'],
            $data['medico_id'], 
            $data['fecha_cita'], 
            $data['hora_cita'], 
            $data['motivo']
        );
        
        if ($stmt->execute()) {
            $citaId = $connection->insert_id;
            
            // Obtener los datos completos de la cita creada
            $getQuery = "SELECT c.id, c.fecha_cita, c.hora_cita, c.motivo, c.estado,
                                CONCAT(up.nombre, ' ', up.apellido) as paciente_nombre,
                                CONCAT(um.nombre, ' ', um.apellido) as medico_nombre,
                                e.nombre as especialidad_nombre
                         FROM citas c
                         INNER JOIN pacientes p ON c.paciente_id = p.id
                         INNER JOIN usuarios up ON p.usuario_id = up.id
                         INNER JOIN medicos m ON c.medico_id = m.id
                         INNER JOIN usuarios um ON m.usuario_id = um.id
                         INNER JOIN especialidades e ON m.especialidad_id = e.id
                         WHERE c.id = ?";
            $stmt = $connection->prepare($getQuery);
            $stmt->bind_param("i", $citaId);
            $stmt->execute();
            $result = $stmt->get_result();
            $cita = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => 'Cita programada exitosamente',
                'cita' => $cita
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al programar la cita']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor',
            'details' => $e->getMessage()
        ]);
    }
}

function handleGetCitas() {
    try {
        // Conectar a la base de datos
        require_once __DIR__ . '/../config/DatabaseMySQLi.php';
    
    $db = new Database();
    $connection = $db->getConnection();
    
    // Parámetros opcionales
    $paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : null;
    $medico_id = isset($_GET['medico_id']) ? (int)$_GET['medico_id'] : null;
    $estado = isset($_GET['estado']) ? $_GET['estado'] : null;
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;
    
    // Construir consulta
    $query = "SELECT c.id, c.fecha_cita, c.hora_cita, c.motivo, c.observaciones, c.estado,
                     CONCAT(up.nombre, ' ', up.apellido) as paciente_nombre,
                     p.cedula as paciente_cedula,
                     CONCAT(um.nombre, ' ', um.apellido) as medico_nombre,
                     m.cedula as medico_cedula,
                     e.nombre as especialidad_nombre,
                     c.fecha_creacion, c.fecha_actualizacion
              FROM citas c
              INNER JOIN pacientes p ON c.paciente_id = p.id
              INNER JOIN usuarios up ON p.usuario_id = up.id
              INNER JOIN medicos m ON c.medico_id = m.id
              INNER JOIN usuarios um ON m.usuario_id = um.id
              INNER JOIN especialidades e ON m.especialidad_id = e.id
              WHERE 1=1";
    
    // Agregar filtros
    if ($paciente_id) {
        $query .= " AND c.paciente_id = $paciente_id";
    }
    
    if ($medico_id) {
        $query .= " AND c.medico_id = $medico_id";
    }
    
    if ($estado) {
        $estado_escaped = $connection->real_escape_string($estado);
        $query .= " AND c.estado = '$estado_escaped'";
    }
    
    if ($fecha_desde) {
        $fecha_desde_escaped = $connection->real_escape_string($fecha_desde);
        $query .= " AND c.fecha_cita >= '$fecha_desde_escaped'";
    }
    
    if ($fecha_hasta) {
        $fecha_hasta_escaped = $connection->real_escape_string($fecha_hasta);
        $query .= " AND c.fecha_cita <= '$fecha_hasta_escaped'";
    }
    
    $query .= " ORDER BY c.fecha_cita DESC, c.hora_cita DESC";
    
    $result = $connection->query($query);
    
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        $citas[] = [
            'id' => $row['id'],
            'fecha_cita' => $row['fecha_cita'],
            'hora_cita' => $row['hora_cita'],
            'motivo' => $row['motivo'],
            'observaciones' => $row['observaciones'],
            'estado' => $row['estado'],
            'paciente_nombre' => $row['paciente_nombre'],
            'paciente_cedula' => $row['paciente_cedula'],
            'medico_nombre' => $row['medico_nombre'],
            'medico_cedula' => $row['medico_cedula'],
            'especialidad_nombre' => $row['especialidad_nombre'],
            'fecha_creacion' => $row['fecha_creacion'],
            'fecha_actualizacion' => $row['fecha_actualizacion']
        ];
    }
    
        echo json_encode([
            'success' => true,
            'citas' => $citas,
            'total' => count($citas)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error interno del servidor',
            'details' => $e->getMessage()
        ]);
    }
}
?>
