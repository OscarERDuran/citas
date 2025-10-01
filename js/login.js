// Funcionalidad para el login
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            
            // Validación básica
            if (!email || !password) {
                showAlert('Por favor, completa todos los campos', 'warning');
                return;
            }
            
            // Autenticación real con API
            showLoading(true);
            
            fetch('backend/api/login_simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                
                if (data.success) {
                    // Guardar datos de sesión
                    localStorage.setItem('userToken', data.token);
                    localStorage.setItem('userData', JSON.stringify(data.user));
                    
                    if (remember) {
                        localStorage.setItem('rememberUser', email);
                    }
                    
                    showAlert('¡Login exitoso! Redirigiendo...', 'success');
                    
                    // Redireccionar después de un momento
                    setTimeout(() => {
                        window.location.href = 'dashboard.html';
                    }, 1000);
                } else {
                    showAlert(data.message || 'Error en el login', 'danger');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showAlert('Error de conexión. Intenta nuevamente.', 'danger');
            });
        });
    }
    
    // Cargar email guardado si existe
    const rememberedUser = localStorage.getItem('rememberUser');
    if (rememberedUser) {
        document.getElementById('email').value = rememberedUser;
        document.getElementById('remember').checked = true;
    }
});

// Función para mostrar alertas
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar la alerta al principio del formulario
    const form = document.getElementById('loginForm');
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
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando sesión...';
    } else {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Iniciar sesión';
    }
}
