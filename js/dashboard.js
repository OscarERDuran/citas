// Funcionalidad para el dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticación
    const userData = localStorage.getItem('userData');
    const userToken = localStorage.getItem('userToken');
    
    if (!userData || !userToken) {
        // Redireccionar al login si no está autenticado
        window.location.href = 'index.html';
        return;
    }
    
    // Mostrar información del usuario
    const user = JSON.parse(userData);
    displayUserInfo(user);
    
    // Cargar especialidades reales
    loadEspecialidades();
    
    // Configurar menú móvil
    setupMobileMenu();
    
    // Animaciones de entrada para las cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
    
    // Funcionalidad para las especialidades
    const especialidades = document.querySelectorAll('.badge.bg-light');
    especialidades.forEach(badge => {
        badge.addEventListener('click', function() {
            const especialidad = this.textContent.trim();
            
            // Redireccionar a programar cita con la especialidad preseleccionada
            const url = new URL('programar-cita.html', window.location.origin + window.location.pathname);
            url.searchParams.set('especialidad', getEspecialidadValue(especialidad));
            window.location.href = url.toString();
        });
        
        // Agregar cursor pointer
        badge.style.cursor = 'pointer';
    });
    
    // Mostrar mensaje de bienvenida
    showWelcomeMessage();
});

// Obtener valor de especialidad para URL
function getEspecialidadValue(especialidadName) {
    const especialidadMap = {
        'Medicina General': 'medicina-general',
        'Cardiología': 'cardiologia',
        'Ginecología': 'ginecologia',
        'Psicología': 'psicologia',
        'Dermatología': 'dermatologia',
        'Odontología': 'odontologia'
    };
    
    return especialidadMap[especialidadName] || '';
}

// Mostrar información del usuario
function displayUserInfo(user) {
    // Actualizar saludo en el título
    const titleElement = document.querySelector('h1');
    if (titleElement) {
        titleElement.textContent = `Bienvenido, ${user.nombre} ${user.apellido}`;
    }
    
    // Mostrar rol del usuario
    const roleElement = document.querySelector('.lead');
    if (roleElement) {
        const roleText = {
            'administrador': 'Administrador del Sistema',
            'medico': 'Médico',
            'paciente': 'Paciente'
        };
        roleElement.textContent = `${roleText[user.rol] || user.rol} - ${roleElement.textContent}`;
    }
    
    // Mostrar mensaje de bienvenida personalizado
    showWelcomeMessage(user);
}

// Mostrar mensaje de bienvenida
function showWelcomeMessage(user = null) {
    const userName = user ? `${user.nombre} ${user.apellido}` : 'Usuario';
    
    // Crear toast de bienvenida
    const toastHTML = `
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="welcomeToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-heartbeat text-primary me-2"></i>
                    <strong class="me-auto">Famicitas</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ¡Bienvenido ${userName}! Gestiona tus citas médicas de forma fácil y rápida.
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toastHTML);
    
    const toast = new bootstrap.Toast(document.getElementById('welcomeToast'));
    toast.show();
}

// Función para logout
function logout() {
    // Limpiar datos de sesión
    localStorage.removeItem('userToken');
    localStorage.removeItem('userData');
    localStorage.removeItem('rememberUser');
    
    // Redireccionar al login
    window.location.href = 'index.html';
}

// Configurar menú móvil
function setupMobileMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    // Cerrar menú al hacer clic en un enlace (móvil)
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                    toggle: false
                });
                bsCollapse.hide();
            }
        });
    });
    
    // Cambiar icono del botón hamburguesa
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            setTimeout(() => {
                const isExpanded = navbarCollapse.classList.contains('show');
                if (isExpanded) {
                    this.setAttribute('aria-expanded', 'true');
                } else {
                    this.setAttribute('aria-expanded', 'false');
                }
            }, 100);
        });
    }
}

// Cargar especialidades desde la API
function loadEspecialidades() {
    fetch('backend/api/especialidades.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateEspecialidadesSection(data.especialidades);
            }
        })
        .catch(error => {
            console.error('Error cargando especialidades:', error);
        });
}

// Actualizar sección de especialidades
function updateEspecialidadesSection(especialidades) {
    const especialidadesContainer = document.querySelector('.row').lastElementChild.querySelector('.d-flex');
    if (especialidadesContainer) {
        especialidadesContainer.innerHTML = '';
        
        especialidades.forEach(especialidad => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-light text-dark me-2 mb-2';
            badge.style.cursor = 'pointer';
            badge.textContent = especialidad.nombre;
            badge.addEventListener('click', function() {
                // Redireccionar a programar cita con la especialidad preseleccionada
                const url = new URL('programar-cita.html', window.location.origin + window.location.pathname.replace('dashboard.html', ''));
                url.searchParams.set('especialidad_id', especialidad.id);
                url.searchParams.set('especialidad_nombre', especialidad.nombre);
                window.location.href = url.toString();
            });
            
            especialidadesContainer.appendChild(badge);
        });
    }
}

// Estadísticas rápidas (para futuras implementaciones)
function loadQuickStats() {
    // Aquí se cargarían estadísticas desde PHP
    const stats = {
        citasHoy: 3,
        citasPendientes: 5,
        especialidadesActivas: 6
    };
    
    return stats;
}
