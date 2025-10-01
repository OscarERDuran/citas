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

// Archivo para almacenar citas (simulando base de datos)
$citasFile = __DIR__ . '/../data/citas.json';
$pacientesFile = __DIR__ . '/../data/pacientes.json';

// Crear directorios si no existen
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
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
    global $citasFile, $pacientesFile;
    
    try {
        // Obtener datos del cuerpo de la petición
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Log para debug
        error_log("=== CREAR CITA ===");
        error_log("JSON recibido: " . $json);
        error_log("Datos decodificados: " . print_r($data, true));
        error_log("Archivo citas: " . $citasFile);
        
        // Validar datos requeridos
        if (!isset($data['medico']) || !isset($data['especialidad']) || 
            !isset($data['fecha']) || !isset($data['hora']) || !isset($data['motivo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        // Leer citas existentes
        $citas = [];
        if (file_exists($citasFile)) {
            $citas = json_decode(file_get_contents($citasFile), true) ?: [];
        }
        
        // Verificar disponibilidad
        foreach ($citas as $cita) {
            if ($cita['medico_id'] == $data['medico'] && 
                $cita['fecha'] == $data['fecha'] && 
                $cita['hora'] == $data['hora'] &&
                in_array($cita['estado'], ['programada', 'confirmada'])) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'El médico no está disponible en esa fecha y hora']);
                return;
            }
        }
        
        // Obtener paciente de los datos de usuario logueado
        $userData = isset($_SESSION['user']) ? $_SESSION['user'] : [
            'id' => 1,
            'nombre' => 'Paciente',
            'apellido' => 'Demo',
            'email' => 'paciente@demo.com'
        ];
        
        // Crear nueva cita
        $nuevaCita = [
            'id' => count($citas) + 1,
            'paciente_id' => $userData['id'],
            'paciente_nombre' => $userData['nombre'] . ' ' . $userData['apellido'],
            'medico_id' => (int)$data['medico'],
            'especialidad_id' => (int)$data['especialidad'],
            'fecha' => $data['fecha'],
            'hora' => $data['hora'],
            'motivo' => $data['motivo'],
            'estado' => 'programada',
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        
        // Agregar cita al array
        $citas[] = $nuevaCita;
        
        // Guardar en archivo
        $resultado = file_put_contents($citasFile, json_encode($citas, JSON_PRETTY_PRINT));
        error_log("Resultado de escritura: " . ($resultado ? "ÉXITO" : "ERROR"));
        error_log("Archivo existe después de escribir: " . (file_exists($citasFile) ? "SÍ" : "NO"));
        
        if ($resultado === false) {
            throw new Exception("No se pudo escribir en el archivo de citas");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cita programada exitosamente',
            'cita' => $nuevaCita
        ]);
        
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
    global $citasFile;
    
    try {
        error_log("=== OBTENER CITAS ===");
        error_log("Archivo citas: " . $citasFile);
        error_log("Archivo existe: " . (file_exists($citasFile) ? "SÍ" : "NO"));
        
        // Leer citas existentes
        $citas = [];
        if (file_exists($citasFile)) {
            $contenido = file_get_contents($citasFile);
            error_log("Contenido del archivo: " . $contenido);
            $citas = json_decode($contenido, true) ?: [];
        }
        
        error_log("Número de citas encontradas: " . count($citas));
        
        echo json_encode([
            'success' => true,
            'citas' => $citas
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor',
            'details' => $e->getMessage()
        ]);
    }
}
?>