// Funcionalidad para el registro
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                nombres: document.getElementById('nombres').value,
                apellidos: document.getElementById('apellidos').value,
                documento: document.getElementById('documento').value,
                telefono: document.getElementById('telefono').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                confirmPassword: document.getElementById('confirmPassword').value
            };
            
            // Validaciones
            if (!validateForm(formData)) {
                return;
            }
            
            // Simulación de registro
            showLoading(true);
            
            setTimeout(() => {
                showLoading(false);
                
                // Aquí iría la lógica de registro con PHP
                showAlert('Usuario registrado exitosamente', 'success');
                
                // Redireccionar al login después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 2000);
                
            }, 1500);
        });
    }
});

// Validar formulario
function validateForm(data) {
    // Verificar campos vacíos
    for (let field in data) {
        if (!data[field].trim()) {
            showAlert('Por favor, completa todos los campos', 'warning');
            return false;
        }
    }
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(data.email)) {
        showAlert('Por favor, ingresa un email válido', 'warning');
        return false;
    }
    
    // Validar contraseñas
    if (data.password.length < 6) {
        showAlert('La contraseña debe tener al menos 6 caracteres', 'warning');
        return false;
    }
    
    if (data.password !== data.confirmPassword) {
        showAlert('Las contraseñas no coinciden', 'warning');
        return false;
    }
    
    // Validar documento (solo números)
    if (!/^\d+$/.test(data.documento)) {
        showAlert('El documento debe contener solo números', 'warning');
        return false;
    }
    
    // Validar teléfono (solo números)
    if (!/^\d+$/.test(data.telefono)) {
        showAlert('El teléfono debe contener solo números', 'warning');
        return false;
    }
    
    return true;
}

// Función para mostrar alertas
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar la alerta al principio del formulario
    const form = document.getElementById('registerForm');
    form.insertBefore(alertDiv, form.firstChild);
    
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
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando...';
    } else {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Registrarse';
    }
}
