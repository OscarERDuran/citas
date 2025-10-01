// Funcionalidad para registro de pacientes
document.addEventListener('DOMContentLoaded', function() {
    const registroPacienteForm = document.getElementById('registroPacienteForm');
    
    // Configurar fecha máxima (hoy) para fecha de nacimiento
    const fechaNacimiento = document.getElementById('fechaNacimiento');
    const today = new Date().toISOString().split('T')[0];
    fechaNacimiento.max = today;
    
    if (registroPacienteForm) {
        registroPacienteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = getFormData();
            
            // Validar formulario
            if (!validatePacienteForm(formData)) {
                return;
            }
            
            // Registrar paciente real con API
            showLoading(true);
            
            fetch('backend/api/pacientes_real.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                
                if (data.success) {
                    showSuccessModal(formData);
                } else {
                    showAlert(data.message || 'Error al registrar paciente', 'danger');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showAlert('Error de conexión al registrar paciente', 'danger');
            });
        });
    }
    
    // Validación en tiempo real para documento
    document.getElementById('documento').addEventListener('input', function() {
        const tipoDoc = document.getElementById('tipoDocumento').value;
        const documento = this.value;
        
        if (tipoDoc && documento) {
            validateDocumento(tipoDoc, documento);
        }
    });
    
    // Calcular edad automáticamente
    document.getElementById('fechaNacimiento').addEventListener('change', function() {
        const edad = calculateAge(this.value);
        if (edad >= 0) {
            showAgeInfo(edad);
        }
    });
});

// Obtener datos del formulario
function getFormData() {
    return {
        nombres: document.getElementById('nombres').value.trim(),
        apellidos: document.getElementById('apellidos').value.trim(),
        tipoDocumento: document.getElementById('tipoDocumento').value,
        documento: document.getElementById('documento').value.trim(),
        fechaNacimiento: document.getElementById('fechaNacimiento').value,
        genero: document.getElementById('genero').value,
        telefono: document.getElementById('telefono').value.trim(),
        email: document.getElementById('email').value.trim(),
        direccion: document.getElementById('direccion').value.trim(),
        ciudad: document.getElementById('ciudad').value.trim(),
        departamento: document.getElementById('departamento').value,
        eps: document.getElementById('eps').value.trim(),
        tipoSangre: document.getElementById('tipoSangre').value,
        alergias: document.getElementById('alergias').value.trim(),
        observaciones: document.getElementById('observaciones').value.trim(),
        aceptoTerminos: document.getElementById('aceptoTerminos').checked
    };
}

// Validar formulario de paciente
function validatePacienteForm(data) {
    // Campos obligatorios
    const camposObligatorios = [
        'nombres', 'apellidos', 'tipoDocumento', 'documento', 
        'fechaNacimiento', 'genero', 'telefono', 'email', 
        'direccion', 'ciudad', 'departamento'
    ];
    
    for (let campo of camposObligatorios) {
        if (!data[campo]) {
            showAlert(`El campo ${getFieldDisplayName(campo)} es obligatorio`, 'warning');
            document.getElementById(campo).focus();
            return false;
        }
    }
    
    // Validar términos y condiciones
    if (!data.aceptoTerminos) {
        showAlert('Debes aceptar los términos y condiciones', 'warning');
        return false;
    }
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(data.email)) {
        showAlert('Por favor, ingresa un email válido', 'warning');
        document.getElementById('email').focus();
        return false;
    }
    
    // Validar teléfono
    const telefonoRegex = /^[0-9+\-\s()]+$/;
    if (!telefonoRegex.test(data.telefono)) {
        showAlert('El teléfono debe contener solo números y caracteres válidos', 'warning');
        document.getElementById('telefono').focus();
        return false;
    }
    
    // Validar edad
    const edad = calculateAge(data.fechaNacimiento);
    if (edad < 0 || edad > 150) {
        showAlert('Por favor, ingresa una fecha de nacimiento válida', 'warning');
        document.getElementById('fechaNacimiento').focus();
        return false;
    }
    
    return true;
}

// Validar documento según tipo
function validateDocumento(tipoDoc, documento) {
    let isValid = true;
    let message = '';
    
    switch (tipoDoc) {
        case 'cedula':
            // Cédula colombiana: solo números, entre 6 y 10 dígitos
            if (!/^\d{6,10}$/.test(documento)) {
                isValid = false;
                message = 'La cédula debe tener entre 6 y 10 dígitos';
            }
            break;
        case 'tarjeta-identidad':
            // TI: solo números, entre 8 y 11 dígitos
            if (!/^\d{8,11}$/.test(documento)) {
                isValid = false;
                message = 'La tarjeta de identidad debe tener entre 8 y 11 dígitos';
            }
            break;
        case 'cedula-extranjeria':
            // CE: puede contener números y letras
            if (!/^[A-Z0-9]{6,12}$/i.test(documento)) {
                isValid = false;
                message = 'La cédula de extranjería debe tener entre 6 y 12 caracteres alfanuméricos';
            }
            break;
        case 'pasaporte':
            // Pasaporte: formato internacional
            if (!/^[A-Z0-9]{6,9}$/i.test(documento)) {
                isValid = false;
                message = 'El pasaporte debe tener entre 6 y 9 caracteres alfanuméricos';
            }
            break;
    }
    
    const documentoInput = document.getElementById('documento');
    
    if (!isValid) {
        documentoInput.classList.add('is-invalid');
        showFieldError('documento', message);
    } else {
        documentoInput.classList.remove('is-invalid');
        documentoInput.classList.add('is-valid');
        removeFieldError('documento');
    }
    
    return isValid;
}

// Calcular edad
function calculateAge(birthDate) {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    
    return age;
}

// Mostrar información de edad
function showAgeInfo(edad) {
    const fechaNacimientoDiv = document.getElementById('fechaNacimiento').parentNode;
    let ageInfo = fechaNacimientoDiv.querySelector('.age-info');
    
    if (!ageInfo) {
        ageInfo = document.createElement('small');
        ageInfo.className = 'age-info text-muted mt-1 d-block';
        fechaNacimientoDiv.appendChild(ageInfo);
    }
    
    ageInfo.textContent = `Edad: ${edad} años`;
}

// Mostrar modal de éxito
function showSuccessModal(data) {
    const modalHTML = `
        <div class="modal fade" id="successModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Paciente Registrado
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-user-check text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-center mb-3">¡Registro exitoso!</h4>
                        <div class="alert alert-success">
                            <strong>${data.nombres} ${data.apellidos}</strong> ha sido registrado exitosamente en el sistema.
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <strong>Documento:</strong><br>
                                ${data.documento}
                            </div>
                            <div class="col-6">
                                <strong>Teléfono:</strong><br>
                                ${data.telefono}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" onclick="redirectToDashboard()">
                            <i class="fas fa-home me-2"></i>Ir al Dashboard
                        </button>
                        <button type="button" class="btn btn-primary" onclick="registerAnother()">
                            <i class="fas fa-plus me-2"></i>Registrar Otro
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
}

// Funciones auxiliares
function getFieldDisplayName(fieldName) {
    const displayNames = {
        'nombres': 'Nombres',
        'apellidos': 'Apellidos',
        'tipoDocumento': 'Tipo de Documento',
        'documento': 'Número de Documento',
        'fechaNacimiento': 'Fecha de Nacimiento',
        'genero': 'Género',
        'telefono': 'Teléfono',
        'email': 'Correo Electrónico',
        'direccion': 'Dirección',
        'ciudad': 'Ciudad',
        'departamento': 'Departamento'
    };
    
    return displayNames[fieldName] || fieldName;
}

function showFieldError(fieldId, message) {
    removeFieldError(fieldId);
    
    const field = document.getElementById(fieldId);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function removeFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const existingError = field.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
}

// Funciones del modal
window.redirectToDashboard = function() {
    window.location.href = 'dashboard.html';
};

window.registerAnother = function() {
    document.getElementById('successModal').remove();
    document.getElementById('registroPacienteForm').reset();
    document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
        el.classList.remove('is-valid', 'is-invalid');
    });
    window.scrollTo(0, 0);
};

// Función para mostrar alertas
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container .row .col-md-8');
    const card = container.querySelector('.card');
    container.insertBefore(alertDiv, card);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
    
    // Scroll hacia la alerta
    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Función para mostrar estado de carga
function showLoading(show) {
    const submitBtn = document.querySelector('button[type="submit"]');
    
    if (show) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando...';
    } else {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Registrar Paciente';
    }
}
