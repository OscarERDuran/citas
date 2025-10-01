<?php
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Controlador de Citas
 */

class CitaController extends BaseController {
    
    private $citaModel;
    
    public function __construct() {
        parent::__construct();
        $this->citaModel = new Cita();
    }
    
    /**
     * Listar citas con filtros
     * GET /api/citas
     */
    public function index($params = []) {
        try {
            $filters = [
                'paciente_id' => $_GET['paciente_id'] ?? null,
                'medico_id' => $_GET['medico_id'] ?? null,
                'especialidad_id' => $_GET['especialidad_id'] ?? null,
                'estado' => $_GET['estado'] ?? null,
                'fecha_desde' => $_GET['fecha_desde'] ?? null,
                'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
                'fecha_cita' => $_GET['fecha_cita'] ?? null
            ];
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            // Verificar permisos
            $currentUser = $GLOBALS['current_user'];
            
            if ($currentUser['rol'] === 'paciente') {
                $filters['paciente_id'] = $currentUser['id'];
            } elseif ($currentUser['rol'] === 'medico') {
                $filters['medico_id'] = $currentUser['id'];
            }
            
            $citas = $this->citaModel->listarConFiltros($filters, $limit, $offset);
            $total = $this->citaModel->contarConFiltros($filters);
            
            $this->jsonResponse([
                'citas' => $citas,
                'pagination' => $this->getPaginationData($total, $page, $limit)
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse('Error al obtener citas: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener cita por ID
     * GET /api/citas/{id}
     */
    public function show($params) {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                $this->errorResponse('ID de cita requerido', 400);
                return;
            }
            
            $cita = $this->citaModel->obtenerConDetalles($id);
            
            if (!$cita) {
                $this->errorResponse('Cita no encontrada', 404);
                return;
            }
            
            // Verificar permisos
            $currentUser = $GLOBALS['current_user'];
            
            if ($currentUser['rol'] === 'paciente' && $cita['paciente_id'] != $currentUser['id']) {
                $this->errorResponse('No autorizado', 403);
                return;
            } elseif ($currentUser['rol'] === 'medico' && $cita['medico_id'] != $currentUser['id']) {
                $this->errorResponse('No autorizado', 403);
                return;
            }
            
            $this->jsonResponse(['cita' => $cita]);
            
        } catch (Exception $e) {
            $this->errorResponse('Error al obtener cita: ' . $e->getMessage());
        }
    }
    
    /**
     * Crear nueva cita
     * POST /api/citas
     */
    public function store($params = []) {
        try {
            $data = $this->getJsonInput();
            
            // Validar datos requeridos
            $required = ['paciente_id', 'medico_id', 'fecha_cita', 'hora_cita', 'motivo'];
            $validation = $this->validateRequired($data, $required);
            
            if (!$validation['valid']) {
                $this->errorResponse($validation['message'], 400);
                return;
            }
            
            // Verificar permisos
            $currentUser = $GLOBALS['current_user'];
            
            if ($currentUser['rol'] === 'paciente' && $data['paciente_id'] != $currentUser['id']) {
                $this->errorResponse('Solo puedes crear citas para ti mismo', 403);
                return;
            }
            
            // Verificar disponibilidad
            if (!$this->citaModel->verificarDisponibilidad($data['medico_id'], $data['fecha_cita'], $data['hora_cita'])) {
                $this->errorResponse('El médico no está disponible en esa fecha y hora', 409);
                return;
            }
            
            // Crear cita
            $data['estado'] = 'programada';
            $data['fecha_creacion'] = date('Y-m-d H:i:s');
            
            $citaId = $this->citaModel->crear($data);
            
            if ($citaId) {
                $cita = $this->citaModel->obtenerConDetalles($citaId);
                $this->jsonResponse(['cita' => $cita], 201);
            } else {
                $this->errorResponse('Error al crear la cita');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Error al crear cita: ' . $e->getMessage());
        }
    }
    
    /**
     * Actualizar cita
     * PUT /api/citas/{id}
     */
    public function update($params) {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                $this->errorResponse('ID de cita requerido', 400);
                return;
            }
            
            $cita = $this->citaModel->buscarPorId($id);
            
            if (!$cita) {
                $this->errorResponse('Cita no encontrada', 404);
                return;
            }
            
            // Verificar permisos
            $currentUser = $GLOBALS['current_user'];
            
            if ($currentUser['rol'] === 'paciente' && $cita['paciente_id'] != $currentUser['id']) {
                $this->errorResponse('No autorizado', 403);
                return;
            } elseif ($currentUser['rol'] === 'medico' && $cita['medico_id'] != $currentUser['id']) {
                $this->errorResponse('No autorizado', 403);
                return;
            }
            
            $data = $this->getJsonInput();
            
            // Los pacientes solo pueden cambiar ciertos campos
            if ($currentUser['rol'] === 'paciente') {
                $allowedFields = ['motivo', 'observaciones'];
                $data = array_intersect_key($data, array_flip($allowedFields));
            }
            
            // Si se cambia fecha/hora, verificar disponibilidad
            if (isset($data['fecha_cita']) || isset($data['hora_cita'])) {
                $newFecha = $data['fecha_cita'] ?? $cita['fecha_cita'];
                $newHora = $data['hora_cita'] ?? $cita['hora_cita'];
                
                if (!$this->citaModel->verificarDisponibilidad($cita['medico_id'], $newFecha, $newHora, $id)) {
                    $this->errorResponse('El médico no está disponible en esa fecha y hora', 409);
                    return;
                }
            }
            
            $data['fecha_actualizacion'] = date('Y-m-d H:i:s');
            
            if ($this->citaModel->actualizar($id, $data)) {
                $citaActualizada = $this->citaModel->obtenerConDetalles($id);
                $this->jsonResponse(['cita' => $citaActualizada]);
            } else {
                $this->errorResponse('Error al actualizar la cita');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Error al actualizar cita: ' . $e->getMessage());
        }
    }
    
    /**
     * Cambiar estado de cita
     * POST /api/citas/{id}/estado
     */
    public function cambiarEstado($params) {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                $this->errorResponse('ID de cita requerido', 400);
                return;
            }
            
            $data = $this->getJsonInput();
            $nuevoEstado = $data['estado'] ?? null;
            
            $estadosValidos = ['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'];
            
            if (!in_array($nuevoEstado, $estadosValidos)) {
                $this->errorResponse('Estado inválido', 400);
                return;
            }
            
            $cita = $this->citaModel->buscarPorId($id);
            
            if (!$cita) {
                $this->errorResponse('Cita no encontrada', 404);
                return;
            }
            
            // Verificar permisos
            $currentUser = $GLOBALS['current_user'];
            
            if ($currentUser['rol'] === 'paciente') {
                // Los pacientes solo pueden cancelar
                if ($nuevoEstado !== 'cancelada') {
                    $this->errorResponse('Solo puedes cancelar citas', 403);
                    return;
                }
                
                if ($cita['paciente_id'] != $currentUser['id']) {
                    $this->errorResponse('No autorizado', 403);
                    return;
                }
            } elseif ($currentUser['rol'] === 'medico') {
                if ($cita['medico_id'] != $currentUser['id']) {
                    $this->errorResponse('No autorizado', 403);
                    return;
                }
            }
            
            $updateData = [
                'estado' => $nuevoEstado,
                'fecha_actualizacion' => date('Y-m-d H:i:s')
            ];
            
            // Agregar observaciones si se proporcionan
            if (isset($data['observaciones'])) {
                $updateData['observaciones'] = $data['observaciones'];
            }
            
            if ($this->citaModel->actualizar($id, $updateData)) {
                $citaActualizada = $this->citaModel->obtenerConDetalles($id);
                $this->jsonResponse(['cita' => $citaActualizada]);
            } else {
                $this->errorResponse('Error al cambiar estado de la cita');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Error al cambiar estado: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener horarios disponibles
     * GET /api/citas/disponibilidad/{medico_id}/{fecha}
     */
    public function disponibilidad($params) {
        try {
            $medicoId = $params['medico_id'] ?? null;
            $fecha = $params['fecha'] ?? null;
            
            if (!$medicoId || !$fecha) {
                $this->errorResponse('Médico ID y fecha requeridos', 400);
                return;
            }
            
            $horariosDisponibles = $this->citaModel->obtenerHorariosDisponibles($medicoId, $fecha);
            
            $this->jsonResponse(['horarios_disponibles' => $horariosDisponibles]);
            
        } catch (Exception $e) {
            $this->errorResponse('Error al obtener disponibilidad: ' . $e->getMessage());
        }
    }
    
    /**
     * Eliminar cita (solo administradores)
     * DELETE /api/citas/{id}
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
            
            if ($this->citaModel->eliminar($id)) {
                $this->jsonResponse(['message' => 'Cita eliminada correctamente']);
            } else {
                $this->errorResponse('Error al eliminar la cita');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Error al eliminar cita: ' . $e->getMessage());
        }
    }
}
?>
