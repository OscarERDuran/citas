<?php
// Autoload de clases
spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/../controllers/',
        __DIR__ . '/../models/',
        __DIR__ . '/../middleware/',
        __DIR__ . '/../utils/',
        __DIR__ . '/../config/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Configuración
require_once __DIR__ . '/../config/config.php';

// Middleware CORS
CorsMiddleware::apiHeaders();

// Crear router
$router = new Router();

// Middleware global CORS
$router->middleware('CorsMiddleware');

// ***** RUTAS DE AUTENTICACIÓN *****
$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/logout', 'AuthController@logout', ['AuthMiddleware']);
$router->post('/auth/refresh', 'AuthController@refreshToken', ['AuthMiddleware']);
$router->get('/auth/me', 'AuthController@me', ['AuthMiddleware']);
$router->post('/auth/change-password', 'AuthController@changePassword', ['AuthMiddleware']);

// ***** RUTAS DE CITAS *****
// Todas las rutas de citas requieren autenticación
$citaMiddleware = ['AuthMiddleware'];

$router->get('/citas', 'CitaController@index', $citaMiddleware);
$router->get('/citas/{id}', 'CitaController@show', $citaMiddleware);
$router->post('/citas', 'CitaController@store', $citaMiddleware);
$router->put('/citas/{id}', 'CitaController@update', $citaMiddleware);
$router->delete('/citas/{id}', 'CitaController@destroy', $citaMiddleware);

// Rutas específicas de citas
$router->post('/citas/{id}/estado', 'CitaController@cambiarEstado', $citaMiddleware);
$router->get('/citas/disponibilidad/{medico_id}/{fecha}', 'CitaController@disponibilidad', $citaMiddleware);

// ***** RUTAS DE PACIENTES *****
$pacienteMiddleware = ['AuthMiddleware'];

$router->get('/pacientes', 'PacienteController@index', $pacienteMiddleware);
$router->get('/pacientes/{id}', 'PacienteController@show', $pacienteMiddleware);
$router->post('/pacientes', 'PacienteController@store', $pacienteMiddleware);
$router->put('/pacientes/{id}', 'PacienteController@update', $pacienteMiddleware);
$router->delete('/pacientes/{id}', 'PacienteController@destroy', $pacienteMiddleware);

// Rutas específicas de pacientes
$router->get('/pacientes/{id}/citas', 'PacienteController@citas', $pacienteMiddleware);
$router->post('/pacientes/{id}/estado', 'PacienteController@cambiarEstado', $pacienteMiddleware);
$router->get('/pacientes/buscar', 'PacienteController@buscar', $pacienteMiddleware);

// ***** RUTAS DE MÉDICOS *****
$medicoMiddleware = ['AuthMiddleware'];

$router->get('/medicos', 'MedicoController@index', $medicoMiddleware);
$router->get('/medicos/{id}', 'MedicoController@show', $medicoMiddleware);
$router->post('/medicos', 'MedicoController@store', $medicoMiddleware);
$router->put('/medicos/{id}', 'MedicoController@update', $medicoMiddleware);
$router->delete('/medicos/{id}', 'MedicoController@destroy', $medicoMiddleware);

// Rutas específicas de médicos
$router->get('/medicos/{id}/citas', 'MedicoController@citas', $medicoMiddleware);
$router->get('/medicos/{id}/horarios', 'MedicoController@horarios', $medicoMiddleware);
$router->post('/medicos/{id}/horarios', 'MedicoController@setHorarios', $medicoMiddleware);
$router->post('/medicos/{id}/estado', 'MedicoController@cambiarEstado', $medicoMiddleware);
$router->get('/medicos/especialidad/{especialidad_id}', 'MedicoController@porEspecialidad', $medicoMiddleware);

// ***** RUTAS DE ESPECIALIDADES *****
$especialidadMiddleware = ['AuthMiddleware'];

$router->get('/especialidades', 'EspecialidadController@index', $especialidadMiddleware);
$router->get('/especialidades/{id}', 'EspecialidadController@show', $especialidadMiddleware);
$router->post('/especialidades', 'EspecialidadController@store', $especialidadMiddleware);
$router->put('/especialidades/{id}', 'EspecialidadController@update', $especialidadMiddleware);
$router->delete('/especialidades/{id}', 'EspecialidadController@destroy', $especialidadMiddleware);

// Rutas específicas de especialidades
$router->get('/especialidades/{id}/medicos', 'EspecialidadController@medicos', $especialidadMiddleware);
$router->post('/especialidades/{id}/estado', 'EspecialidadController@cambiarEstado', $especialidadMiddleware);

// ***** RUTAS DE ADMINISTRACIÓN *****
// Solo para administradores
$adminMiddleware = ['AuthMiddleware']; // El middleware AuthMiddleware ya verifica roles

$router->get('/admin/stats', 'AdminController@stats', $adminMiddleware);
$router->get('/admin/usuarios', 'AdminController@usuarios', $adminMiddleware);
$router->post('/admin/usuarios/{id}/estado', 'AdminController@cambiarEstadoUsuario', $adminMiddleware);
$router->get('/admin/reportes/citas', 'AdminController@reporteCitas', $adminMiddleware);
$router->get('/admin/reportes/pacientes', 'AdminController@reportePacientes', $adminMiddleware);

// ***** RUTAS DE UTILIDAD *****
$router->get('/test', function() {
    echo json_encode([
        'message' => 'API Famicitas funcionando correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
});

// Ruta de salud (health check)
$router->get('/health', function() {
    try {
        // Verificar conexión a base de datos
        $db = new Database();
        $connection = $db->getConnection();
        
        echo json_encode([
            'status' => 'ok',
            'database' => 'connected',
            'timestamp' => date('Y-m-d H:i:s')
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
});

// Manejar errores no capturados
set_exception_handler(function($exception) {
    error_log('Uncaught exception: ' . $exception->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error interno del servidor',
        'details' => DEBUG_MODE ? $exception->getMessage() : null
    ]);
});

// Ejecutar router
try {
    $router->run();
} catch (Exception $e) {
    error_log('Router Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error en el router',
        'details' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>
