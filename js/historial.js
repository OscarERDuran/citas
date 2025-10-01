// Funcionalidad para el historial de citas
document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticación
    const userData = localStorage.getItem('userData');
    const userToken = localStorage.getItem('userToken');
    
    if (!userData || !userToken) {
        window.location.href = 'index.html';
        return;
    }
    
    const user = JSON.parse(userData);
    
    // Cargar citas desde la API
    loadCitas(user);
    
    // Cargar filtros
    loadFilterOptions();
});

// Cargar citas desde la API
function loadCitas(user) {
    // Mostrar loading
    const tbody = document.getElementById('tablaCitas');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <br>Cargando citas...
            </td>
        </tr>
    `;
    
    // Usar API real adaptada
    let url = 'backend/api/citas_real.php';
    
    fetch(url, {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('userToken')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.citasOriginales = data.citas;
            renderCitas(data.citas);
            setupFilters(data.citas);
        } else {
            showError('Error al cargar las citas: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error de conexión al cargar las citas');
    });
}

// Cargar opciones de filtros
function loadFilterOptions() {
    // Cargar médicos y especialidades para los filtros
    Promise.all([
        fetch('backend/api/medicos.php'),
        fetch('backend/api/especialidades.php')
    ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([medicosData, especialidadesData]) => {
        if (medicosData.success) {
            updateMedicoFilter(medicosData.medicos);
        }
        if (especialidadesData.success) {
            updateEspecialidadFilter(especialidadesData.especialidades);
        }
    })
    .catch(error => {
        console.error('Error cargando filtros:', error);
    });
}

// Actualizar filtro de médicos
function updateMedicoFilter(medicos) {
    const select = document.getElementById('filtroMedico');
    select.innerHTML = '<option value="">Todos los médicos</option>';
    
    medicos.forEach(medico => {
        const option = document.createElement('option');
        option.value = medico.id;
        option.textContent = `Dr(a). ${medico.nombre_completo}`;
        select.appendChild(option);
    });
}

// Actualizar filtro de especialidades  
function updateEspecialidadFilter(especialidades) {
    const select = document.getElementById('filtroEspecialidad');
    select.innerHTML = '<option value="">Todas las especialidades</option>';
    
    especialidades.forEach(especialidad => {
        const option = document.createElement('option');
        option.value = especialidad.id;
        option.textContent = especialidad.nombre;
        select.appendChild(option);
    });
}

// Mostrar error
function showError(message) {
    const tbody = document.getElementById('tablaCitas');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center text-danger py-4">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <br>${message}
                <br><button class="btn btn-sm btn-outline-primary mt-2" onclick="location.reload()">Reintentar</button>
            </td>
        </tr>
    `;
}

// Renderizar citas en la tabla
function renderCitas(citas) {
    const tbody = document.getElementById('tablaCitas');
    tbody.innerHTML = '';
    
    if (citas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i>
                    <br>No se encontraron citas
                </td>
            </tr>
        `;
        return;
    }
    
    citas.forEach(cita => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(cita.fecha_cita || cita.fecha)}</td>
            <td>${formatTime(cita.hora_cita || cita.hora)}</td>
            <td>${cita.medico_nombre || 'N/A'}</td>
            <td>${cita.especialidad_nombre || cita.especialidad || 'N/A'}</td>
            <td>${getEstadoBadge(cita.estado)}</td>
            <td>${getActionButtons(cita)}</td>
        `;
        tbody.appendChild(row);
    });
}

// Configurar filtros
function setupFilters(citasOriginales) {
    window.filtrarCitas = function() {
        const filtroMedico = document.getElementById('filtroMedico').value;
        const filtroEspecialidad = document.getElementById('filtroEspecialidad').value;
        const filtroFecha = document.getElementById('filtroFecha').value;
        
        let citasFiltradas = citasOriginales;
        
        if (filtroMedico) {
            citasFiltradas = citasFiltradas.filter(cita => {
                const medico = cita.medico_nombre || '';
                return medico.toLowerCase().includes(filtroMedico.toLowerCase());
            });
        }
        
        if (filtroEspecialidad) {
            citasFiltradas = citasFiltradas.filter(cita => {
                const especialidad = cita.especialidad_nombre || cita.especialidad || '';
                return especialidad.toLowerCase().includes(filtroEspecialidad.toLowerCase());
            });
        }
        
        if (filtroFecha) {
            citasFiltradas = citasFiltradas.filter(cita => 
                cita.fecha_cita === filtroFecha
            );
        }
        
        renderCitas(citasFiltradas);
    };
    
    // Limpiar filtros
    window.limpiarFiltros = function() {
        document.getElementById('filtroMedico').value = '';
        document.getElementById('filtroEspecialidad').value = '';
        document.getElementById('filtroFecha').value = '';
        renderCitas(citasOriginales);
    };
}

// Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    const options = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit'
    };
    return date.toLocaleDateString('es-ES', options);
}

// Formatear hora
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const period = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
    return `${displayHour}:${minutes} ${period}`;
}

// Obtener badge de estado
function getEstadoBadge(estado) {
    const estados = {
        'programada': '<span class="badge bg-primary">Programada</span>',
        'confirmada': '<span class="badge bg-success">Confirmada</span>',
        'en_curso': '<span class="badge bg-info">En Curso</span>',
        'completada': '<span class="badge bg-secondary">Completada</span>',
        'cancelada': '<span class="badge bg-danger">Cancelada</span>',
        'no_asistio': '<span class="badge bg-warning">No Asistió</span>'
    };
    
    return estados[estado] || '<span class="badge bg-light text-dark">Desconocido</span>';
}

// Obtener botones de acción
function getActionButtons(cita) {
    if (cita.estado === 'completada' || cita.estado === 'cancelada') {
        return `
            <button class="btn btn-sm btn-outline-info" onclick="verDetalle(${cita.id})" title="Ver detalle">
                <i class="fas fa-eye"></i>
            </button>
        `;
    } else {
        return `
            <button class="btn btn-sm btn-outline-primary me-1" onclick="editarCita(${cita.id})" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="cancelarCita(${cita.id})" title="Cancelar">
                <i class="fas fa-trash"></i>
            </button>
        `;
    }
}

// Funciones de acción
window.verDetalle = function(citaId) {
    showAlert(`Ver detalle de la cita #${citaId}`, 'info');
};

window.editarCita = function(citaId) {
    if (confirm('¿Deseas editar esta cita?')) {
        // Aquí iría la lógica para editar
        window.location.href = `programar-cita.html?edit=${citaId}`;
    }
};

window.cancelarCita = function(citaId) {
    if (confirm('¿Estás seguro de que deseas cancelar esta cita?')) {
        // Aquí iría la lógica para cancelar con PHP
        showAlert('Cita cancelada exitosamente', 'success');
        
        // Recargar la tabla (en un entorno real se haría una petición AJAX)
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
};

// Función para mostrar alertas
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container .row .col-12');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
