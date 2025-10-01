// Funcionalidad común para el menú de navegación
document.addEventListener('DOMContentLoaded', function() {
    setupMobileMenu();
    highlightActiveMenuItem();
});

// Configurar menú móvil
function setupMobileMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    if (!navbarToggler || !navbarCollapse) return;
    
    // Cerrar menú al hacer clic en un enlace (móvil)
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // No cerrar si es el enlace de salir
            if (link.textContent.includes('SALIR')) {
                return;
            }
            
            if (window.innerWidth < 992) {
                const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse) || new bootstrap.Collapse(navbarCollapse, {
                    toggle: false
                });
                bsCollapse.hide();
            }
        });
    });
    
    // Cerrar menú al hacer clic fuera de él
    document.addEventListener('click', function(e) {
        const isClickInsideNav = navbarCollapse.contains(e.target) || navbarToggler.contains(e.target);
        const isNavOpen = navbarCollapse.classList.contains('show');
        
        if (!isClickInsideNav && isNavOpen && window.innerWidth < 992) {
            const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse) || new bootstrap.Collapse(navbarCollapse, {
                toggle: false
            });
            bsCollapse.hide();
        }
    });
    
    // Animación suave para el toggle
    navbarCollapse.addEventListener('show.bs.collapse', function() {
        this.style.transition = 'height 0.35s ease';
    });
    
    navbarCollapse.addEventListener('hide.bs.collapse', function() {
        this.style.transition = 'height 0.35s ease';
    });
}

// Resaltar elemento activo del menú
function highlightActiveMenuItem() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        
        // Comparar con la página actual
        if (href === currentPage || 
            (currentPage === '' && href === 'index.html') ||
            (currentPage === 'dashboard.html' && href === 'dashboard.html') ||
            (currentPage === 'programar-cita.html' && href === 'programar-cita.html') ||
            (currentPage === 'historial.html' && href === 'historial.html') ||
            (currentPage === 'registro-paciente.html' && href === 'registro-paciente.html')) {
            
            link.classList.add('active');
            link.style.color = 'var(--primary-color)';
            link.style.fontWeight = '600';
        }
    });
}

// Confirmar logout
function confirmLogout(event) {
    event.preventDefault();
    
    const logoutModal = `
        <div class="modal fade" id="logoutModal" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-sign-out-alt text-warning me-2"></i>
                            Cerrar Sesión
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p>¿Estás seguro de que deseas cerrar sesión?</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="doLogout()">Salir</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente si existe
    const existingModal = document.getElementById('logoutModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', logoutModal);
    
    const modal = new bootstrap.Modal(document.getElementById('logoutModal'));
    modal.show();
}

// Ejecutar logout
function doLogout() {
    // Limpiar datos de sesión
    localStorage.removeItem('userName');
    localStorage.removeItem('userSession');
    localStorage.removeItem('rememberUser');
    
    // Mostrar mensaje de despedida
    const toast = `
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="logoutToast" class="toast" role="alert">
                <div class="toast-header">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <strong class="me-auto">Famicitas</strong>
                </div>
                <div class="toast-body">
                    Sesión cerrada exitosamente. ¡Hasta pronto!
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toast);
    
    const toastEl = new bootstrap.Toast(document.getElementById('logoutToast'));
    toastEl.show();
    
    // Redireccionar después de un momento
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 1500);
}
