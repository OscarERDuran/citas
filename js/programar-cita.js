// Funcionalidad para programar citas
document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticación
    const userData = localStorage.getItem('userData');
    const userToken = localStorage.getItem('userToken');
    
    if (!userData || !userToken) {
        window.location.href = 'index.html';
        return;
    }
    
    const user = JSON.parse(userData);
    
    const programarCitaForm = document.getElementById('programarCitaForm');
    
    // Configurar fecha mínima (hoy)
    const fechaInput = document.getElementById('fecha');
    const today = new Date().toISOString().split('T')[0];
    fechaInput.min = today;
    
    // Cargar especialidades desde la API
    loadEspecialidades();
    
    // Configurar eventos
    setupEventListeners();
    
    // Verificar parámetros URL
    checkUrlParameters();
});

// Cargar especialidades desde la API
function loadEspecialidades() {
    fetch('backend/api/especialidades.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateEspecialidadSelect(data.especialidades);
            } else {
                showAlert('Error al cargar especialidades', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión al cargar especialidades', 'danger');
        });
}

// Actualizar select de especialidades
function updateEspecialidadSelect(especialidades) {
    const select = document.getElementById('especialidad');
    select.innerHTML = '<option value="">Especialidad</option>';
    
    especialidades.forEach(especialidad => {
        const option = document.createElement('option');
        option.value = especialidad.id;
        option.textContent = especialidad.nombre;
        option.dataset.descripcion = especialidad.descripcion;
        select.appendChild(option);
    });
}

// Configurar event listeners
function setupEventListeners() {
    // Filtrar médicos según especialidad seleccionada
    document.getElementById('especialidad').addEventListener('change', function() {
        const especialidadId = this.value;
        const medicoSelect = document.getElementById('medico');
        
        // Limpiar opciones de médico
        medicoSelect.innerHTML = '<option value="">Médico</option>';
        
        if (especialidadId) {
            loadMedicosPorEspecialidad(especialidadId);
        }
    });
}

// Cargar médicos por especialidad
function loadMedicosPorEspecialidad(especialidadId) {
    fetch(`backend/api/medicos.php?especialidad_id=${especialidadId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMedicoSelect(data.medicos);
            } else {
                showAlert('Error al cargar médicos', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error de conexión al cargar médicos', 'danger');
        });
}

// Actualizar select de médicos
function updateMedicoSelect(medicos) {
    const select = document.getElementById('medico');
    select.innerHTML = '<option value="">Médico</option>';
    
    medicos.forEach(medico => {
        const option = document.createElement('option');
        option.value = medico.id;
        option.textContent = `Dr. ${medico.nombre} ${medico.apellido}`;
        option.dataset.nombre = `${medico.nombre} ${medico.apellido}`;
        select.appendChild(option);
    });
}

// Verificar parámetros URL
function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const especialidadId = urlParams.get('especialidad');
    
    if (especialidadId) {
        setTimeout(() => {
            const especialidadSelect = document.getElementById('especialidad');
            especialidadSelect.value = especialidadId;
            especialidadSelect.dispatchEvent(new Event('change'));
        }, 500);
    }
    
    // Manejar envío del formulario
    const programarCitaForm = document.getElementById('programarCitaForm');
    if (programarCitaForm) {
        programarCitaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                medico: document.getElementById('medico').value,
                especialidad: document.getElementById('especialidad').value,
                hora: document.getElementById('hora').value,
                fecha: document.getElementById('fecha').value,
                motivo: document.getElementById('motivo').value
            };
            
            // Validar formulario
            if (!validateCitaForm(formData)) {
                return;
            }
            
            // Programar cita directamente
            programarCita(formData);
        });
    }
}

// Validar formulario de cita
function validateCitaForm(data) {
    console.log('Validando formulario:', data);
    
    if (!data.medico || !data.especialidad || !data.hora || !data.fecha || !data.motivo) {
        console.log('Validación falló:', {
            medico: !!data.medico,
            especialidad: !!data.especialidad,
            hora: !!data.hora,
            fecha: !!data.fecha,
            motivo: !!data.motivo
        });
        showAlert('Por favor, completa todos los campos', 'warning');
        return false;
    }
    
    // Validar que la fecha no sea en el pasado
    const selectedDate = new Date(data.fecha);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        showAlert('No puedes programar una cita en el pasado', 'warning');
        return false;
    }
    
    // Validar hora de trabajo (8:00 AM - 6:00 PM)
    const [hours, minutes] = data.hora.split(':').map(num => parseInt(num));
    const totalMinutes = hours * 60 + minutes;
    const startWork = 8 * 60; // 8:00 AM
    const endWork = 18 * 60;  // 6:00 PM
    
    if (totalMinutes < startWork || totalMinutes >= endWork) {
        showAlert('El horario de atención es de 8:00 AM a 6:00 PM', 'warning');
        return false;
    }
    
    return true;
}

// Programar cita real
function programarCita(formData) {
    const userToken = localStorage.getItem('userToken');
    const userData = JSON.parse(localStorage.getItem('userData'));
    
    console.log('=== PROGRAMAR CITA ===');
    console.log('FormData:', formData);
    console.log('UserData:', userData);
    
    showLoading(true);
    
    // Preparar datos en el formato que espera la API real
    const citaData = {
        paciente_id: userData.id,
        medico_id: formData.medico,
        especialidad_id: formData.especialidad,
        fecha_cita: formData.fecha,
        hora_cita: formData.hora,
        motivo: formData.motivo,
        estado: 'programada'
    };
    
    console.log('Enviando datos:', citaData);
    
    fetch('backend/api/citas_real.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${userToken}`
        },
        body: JSON.stringify(citaData)
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        
        if (data.success) {
            showAlert('¡Cita programada exitosamente!', 'success');
            
            // Limpiar formulario
            document.getElementById('programarCitaForm').reset();
            
            // Redirigir a historial después de 2 segundos
            setTimeout(() => {
                window.location.href = 'historial.html';
            }, 2000);
        } else {
            showAlert(data.message || 'Error al programar la cita', 'danger');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showAlert('Error de conexión al programar la cita', 'danger');
    });
}

// Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        weekday: 'long'
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

// Función para mostrar alertas
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar la alerta después del título
    const container = document.querySelector('.container .row .col-md-6');
    const card = container.querySelector('.card');
    container.insertBefore(alertDiv, card);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Función para mostrar estado de carga
function showLoading(show) {
    const submitBtn = document.querySelector('button[type="submit"]');
    
    if (show) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>PROCESANDO...';
    } else {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'CONFIRMAR';
    }
}
