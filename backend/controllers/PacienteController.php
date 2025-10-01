<?php
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Controlador de Pacientes
 */

class PacienteController extends BaseController {
    
    private $pacienteModel;
    
    public function __construct() {
        parent::__construct();
        $this->pacienteModel = new Paciente();
    }
    
    /**
     * Listar pacientes
     * GET /api/pacientes
     */
    public function index($params = []) {
        try {
            // Solo administradores y médicos pueden ver todos los pacientes
            $currentUser = $GLOBALS['current_user'];
            
            if (!in_array($currentUser['rol'], ['administrador', 'medico'])) {
                $this->errorResponse('Permisos insuficientes', 403);
                return;
            }
            
            $filters = [
                'nombre' => $_GET['nombre'] ?? null,
                'apellido' => $_GET['apellido'] ?? null,
                'cedula' => $_GET['cedula'] ?? null,
                'telefono' => $_GET['telefono'] ?? null,
                'activo' => $_GET['activo'] ?? null
            ];
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $pacientes = $this->pacienteModel->listarConFiltros($filters, $limit, $offset);
            $total = $this->pacienteModel->contarConFiltros($filters);
            
            $this->jsonResponse([
                'pacientes' => $pacientes,
                'pagination' => $this->getPaginationData($total, $page, $limit)
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse('Error al obtener pacientes: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener paciente por ID
     * GET /api/pacientes/{id}
     */
    public function show($params) {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                $this->errorResponse('ID de paciente requerido', 400);
                return;
            }
            
            $currentUser = $GLOBALS['current_user'];
            
            // Los pacientes solo pueden ver su propio perfil
            if ($currentUser['rol'] === 'paciente' && $currentUser['id'] != $id) {
                $this->errorResponse('No autorizado', 403);
                return;
            }
            
            $paciente = $this->pacienteModel->obtenerConDetalles($id);
            
            if (!$paciente) {
                $this->errorResponse('Paciente no encontrado', 404);
                return;
            }
            
            $this->jsonResponse(['paciente' => $paciente]);
            
        } catch (Exception $e) {
            $this->errorResponse('Error al obtener paciente: ' . $e->getMessage());
        }
    }
    
    /**
     * Crear nuevo paciente
     * POST /api/pacientes
     */
    public function store($params = []) {
        try {
            $data = $this->getJsonInput();
            
            // Validar datos requeridos
            $required = ['nombre', 'apellido', 'cedula', 'email', 'telefono', 'fecha_nacimiento'];
            $validation = $this->validateRequired($data, $required);
            
            if (!$validation['valid']) {
                $this->errorResponse($validation['message'], 400);
                return;
            }
            
            // Validar formato de email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->errorResponse('Email inválido', 400);
                return;
            }
            
            // Verificar si ya existe un paciente con esa cédula
            if ($this->pacienteModel->existePorCedula($data['cedula'])) {
                $this->errorResponse('Ya existe un paciente con esa cédula', 409);
                return;
            }
            
            // Verificar si ya existe un paciente con ese email
            if ($this->pacienteModel->existePorEmail($data['email'])) {
                $this->errorResponse('Ya existe un paciente con ese email', 409);
                return;
            }
            
            // Asignar valores por defecto
            $data['activo'] = $data['activo'] ?? true;
            $data['fecha_registro'] = date('Y-m-d H:i:s');
            
            $pacienteId = $this->pacienteModel->crear($data);
            
            if ($pacienteId) {
                $paciente = $this->pacienteModel->obtenerConDetalles($pacienteId);
                $this->jsonResponse(['paciente' => $paciente], 201);
            } else {
                $this->errorResponse('Error al crear el paciente');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Error al crear paciente: ' . $e->getMessage());
        }
    }
    
    /**
     * Actualizar paciente
     * PUT /api/pacientes/{id}
     */
    public function update($params) {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                $this->errorResponse('ID de paciente requerido', 400);
                return;
            }
            
            $currentUser = $GLOBALS['current_user'];
            
            // Los pacientes solo pueden actualizar su propio perfil
            if ($currentUser['rol'] === 'paciente' && $currentUser['id'] != $id) {
                $this->errorResponse('No autorizado', 403);
                return;
            }
            
            $paciente = $this->pacienteModel->buscarPorId($id);
            
            if (!$paciente) {
                $this->errorResponse('Paciente no encontrado', 404);
                return;
            }
            
            $data = $this->getJsonInput();
            
            // Validar email si se proporciona
            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->errorResponse('Email inválido', 400);
                return;
            }
            
            // Verificar cédula única si se cambia
            if (isset($data['cedula']) && $data['cedula'] !== $paciente['cedula']) {
                if ($this->pacienteModel->existePorCedula($data['cedula'])) {
                    $this->errorResponse('Ya existe un paciente con esa cédula', 409);
                    return;
                }
            }
            
            // Verificar email único si se cambia
            if (isset($data['email']) && $data['email'] !== $paciente['email']) {
                if ($this->pacienteModel->existePorEmail($data['email'])) {
                    $this->errorResponse('Ya existe un paciente con ese email', 409);
                    return;
                }
            }
            
            // Los pacientes no pueden cambiar su estado
            if ($currentUser['rol'] === 'paciente') {
                unset($data['activo']);
            }
            
            $data['fecha_actualizacion'] = date('Y-m-d H:i:s');
            
            if ($this->pacienteModel->actualizar($id, $data)) {
                $pacienteActualizado = $this->pacienteModel->obtenerConDetalles($id);
                $this->jsonResponse(['paciente' => $pacienteActualizado]);
            } else {
                $this->errorResponse('Error al actualizar el paciente');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Error al actualizar paciente: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener historial de citas del paciente
     * GET /api/pacientes/{id}/citas
     */
    public function citas($params) {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                $this->errorResponse('ID de paciente requerido', 400);
                return;
            }
            
            $currentUser = $GLOBALS['current_user'];
            
            // Los pacientes solo pueden ver sus propias citas
            if ($currentUser['rol'] === 'paciente' && $currentUser['id'] != $id) {
                $this->errorResponse('No autorizado', 403);
                return;
            }
            
            $filters = [
                'estado' => $_GET['estado'] ?? null,
                'fecha_desde' => $_GET['fecha_desde'] ?? null,
                'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
                'medico_id' => $_GET['medico_id'] ?? null
            ];
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $citas = $this->pacienteModel->obtenerHistorialCitas($id, $filters, $limit, $offset);
            $total = $this->pacienteModel->contarCitas($id, $filters);
            
            $this->jsonResponse([
                'citas' => $citas,
                'pagination' => $this->getPaginationData($total, $page, $limit)
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse('Error al obtener citas: ' . $e->getMessage());
        }
    }
    
    /**
     * Cambiar estado del paciente
     * POST /api/pacientes/{id}/estado
     */
    public function cambiarEstado($params) {
        try {
            $currentUser = $GLOBALS['current_user'];
            
            if ($currentUser['rol'] !== 'administrador') {
                $this->errorResponse('Permisos insuficientes', 403);
                return;
            }
            
            $id = $params['id'] ?? null;
            
            if (!$id) {
                $this->errorResponse('ID de paciente requerido', 400);
                return;
            }
            
            $data = $this->getJsonInput();
            $activo = isset($data['activo']) ? (bool)$data['activo'] : null;
            
            if ($activo === null) {
                $this->errorResponse('Estado activo requerido', 400);
                return;
            }
            
            $updateData = [
                'activo' => $activo,
                'fecha_actualizacion' => date('Y-m-d H:i:s')
            ];
            
            if ($this->pacienteModel->actualizar($id, $updateData)) {
                $paciente = $this->pacienteModel->obtenerConDetalles($id);
                $this->jsonResponse(['paciente' => $paciente]);
            } else {
                $this->errorResponse('Error al cambiar estado del paciente');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Error al cambiar estado: ' . $e->getMessage());
        }
    }
    
    /**
     * Buscar pacientes por término
     * GET /api/pacientes/buscar?q={termino}
     */
    public function buscar($params = []) {
        try {
            $currentUser = $GLOBALS['current_user'];
            
            if (!in_array($currentUser['rol'], ['administrador', 'medico'])) {
                $this->errorResponse('Permisos insuficientes', 403);
                return;
            }
            
            $termino = $_GET['q'] ?? '';
            
            if (strlen($termino) < 2) {
                $this->errorResponse('El término de búsqueda debe tener al menos 2 caracteres', 400);
                return;
            }
            
            $limit = (int)($_GET['limit'] ?? 10);
            
            $pacientes = $this->pacienteModel->buscarPorTermino($termino, $limit);
            
            $this->jsonResponse(['pacientes' => $pacientes]);
            
        } catch (Exception $e) {
            $this->errorResponse('Error al buscar pacientes: ' . $e->getMessage());
        }
    }
    
    /**
     * Eliminar paciente (solo administradores)
     * DELETE /api/pacientes/{id}
     */
    public function destroy($params) {
        try {
            $currentUser = $GLOBALS['current_user'];
            
            if ($currentUser['rol'] !== 'administrador') {
                $this->errorResponse('Permisos insuficientes', 403);
                return;
            }
            
            $id = $params['id'] ?? null;
            
            if (!$id) {
                $this->errorResponse('ID requerido', 400);
                return;
            }
            
            // Verificar si tiene citas programadas
            if ($this->pacienteModel->tieneCitasProgramadas($id)) {
                $this->errorResponse('No se puede eliminar un paciente con citas programadas', 409);
                return;
            }
            
            if ($this->pacienteModel->eliminar($id)) {
                $this->jsonResponse(['message' => 'Paciente eliminado correctamente']);
            } else {
                $this->errorResponse('Error al eliminar el paciente');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Error al eliminar paciente: ' . $e->getMessage());
        }
    }
}
?>
