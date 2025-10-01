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
        // Obtener datos del cuerpo de la petición
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Log para debug
        error_log("=== CREAR CITA BD ===");
        error_log("JSON recibido: " . $json);
        error_log("Datos decodificados: " . print_r($data, true));
        
        // Validar datos requeridos
        if (!isset($data['paciente_id']) || !isset($data['medico_id']) || 
            !isset($data['fecha_cita']) || !isset($data['hora_cita']) || !isset($data['motivo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        // Conectar a la base de datos
        require_once __DIR__ . '/../config/DatabaseMySQLi.php';
        
        $db = new Database();
        $connection = $db->getConnection();
        
        // Verificar disponibilidad (opcional - comentado por ahora para simplificar)
        /*
        $availabilityQuery = "SELECT id FROM citas 
                             WHERE medico_id = ? AND fecha = ? AND hora = ? 
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
        */
        
        // Insertar nueva cita (usando la estructura real de tu tabla)
        $insertQuery = "INSERT INTO citas (paciente_id, medico_id, fecha, hora, motivo_consulta, estado, fecha_registro) 
                        VALUES (?, ?, ?, ?, ?, 'programada', NOW())";
        $stmt = $connection->prepare($insertQuery);
        
        if (!$stmt) {
            error_log("Error preparando consulta: " . $connection->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $connection->error]);
            return;
        }
        
        $stmt->bind_param("iisss", 
            $data['paciente_id'],
            $data['medico_id'], 
            $data['fecha_cita'], 
            $data['hora_cita'], 
            $data['motivo']
        );
        
        if ($stmt->execute()) {
            $citaId = $connection->insert_id;
            error_log("Cita creada con ID: " . $citaId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cita programada exitosamente',
                'cita' => [
                    'id' => $citaId,
                    'paciente_id' => $data['paciente_id'],
                    'medico_id' => $data['medico_id'],
                    'fecha' => $data['fecha_cita'],
                    'hora' => $data['hora_cita'],
                    'motivo' => $data['motivo'],
                    'estado' => 'programada'
                ]
            ]);
        } else {
            error_log("Error ejecutando consulta: " . $stmt->error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error ejecutando consulta: ' . $stmt->error]);
        }
        
        $connection->close();
        
    } catch (Exception $e) {
        error_log("Error en handleCreateCita: " . $e->getMessage());
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
        error_log("=== OBTENER CITAS BD ===");
        
        // Conectar a la base de datos
        require_once __DIR__ . '/../config/DatabaseMySQLi.php';
        
        $db = new Database();
        $connection = $db->getConnection();
        
        // Obtener todas las citas con información completa
        $query = "SELECT c.*, 
                        p.nombres as paciente_nombres, p.apellidos as paciente_apellidos,
                        m.nombres as medico_nombres, m.apellidos as medico_apellidos
                 FROM citas c
                 LEFT JOIN pacientes p ON c.paciente_id = p.id
                 LEFT JOIN medicos m ON c.medico_id = m.id
                 ORDER BY c.fecha DESC, c.hora DESC";
        $result = $connection->query($query);
        
        $citas = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Formatear los datos para el frontend
                $citas[] = [
                    'id' => $row['id'],
                    'fecha' => $row['fecha'],
                    'hora' => $row['hora'],
                    'estado' => $row['estado'],
                    'motivo' => $row['motivo_consulta'] ?? '',
                    'paciente_nombre' => ($row['paciente_nombres'] ?? '') . ' ' . ($row['paciente_apellidos'] ?? ''),
                    'medico_nombre' => 'Dr. ' . ($row['medico_nombres'] ?? '') . ' ' . ($row['medico_apellidos'] ?? ''),
                    'especialidad' => 'N/A' // Podríamos obtener esto con otra consulta si es necesario
                ];
            }
        }
        
        error_log("Número de citas encontradas en BD: " . count($citas));
        
        echo json_encode([
            'success' => true,
            'citas' => $citas
        ]);
        
        $connection->close();
        
    } catch (Exception $e) {
        error_log("Error en handleGetCitas: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor',
            'details' => $e->getMessage()
        ]);
    }
}
?>