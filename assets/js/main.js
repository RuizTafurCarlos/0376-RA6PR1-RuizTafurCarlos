/**
 * MONCAO SECURE - Main JavaScript
 * Funciones JavaScript generales
 */

// Actualizar hora en tiempo real
function updateTime() {
    const now = new Date();
    const horas = now.getHours().toString().padStart(2, '0');
    const minutos = now.getMinutes().toString().padStart(2, '0');
    const segundos = now.getSeconds().toString().padStart(2, '0');
    
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = horas + ':' + minutos + ':' + segundos;
    }
    
    const dateElement = document.getElementById('current-date');
    if (dateElement) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateElement.textContent = now.toLocaleDateString('es-ES', options);
    }
}

// Iniciar reloj en tiempo real
if (document.getElementById('current-time')) {
    setInterval(updateTime, 1000);
    updateTime();
}

// Confirmar acciones peligrosas
function confirmAction(message) {
    return confirm(message);
}

// Validar formulario de login
function validateLoginForm() {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    let isValid = true;
    let errors = [];
    
    if (!email || !email.value.trim()) {
        errors.push('El email es obligatorio');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        errors.push('El email no es válido');
        isValid = false;
    }
    
    if (!password || !password.value) {
        errors.push('La contraseña es obligatoria');
        isValid = false;
    }
    
    if (!isValid) {
        showAlert(errors.join('\n'), 'danger');
    }
    
    return isValid;
}

// Validar email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Mostrar alertas
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Confirmar antes de enviar formulario
function confirmSubmit(event, message) {
    if (!confirm(message)) {
        event.preventDefault();
        return false;
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Validar cambio de contraseña
function validatePasswordChange() {
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (!currentPassword || !currentPassword.value) {
        showAlert('La contraseña actual es obligatoria', 'danger');
        return false;
    }
    
    if (!newPassword || !newPassword.value) {
        showAlert('La nueva contraseña es obligatoria', 'danger');
        return false;
    }
    
    if (newPassword.value.length < 6) {
        showAlert('La nueva contraseña debe tener al menos 6 caracteres', 'danger');
        return false;
    }
    
    if (newPassword.value !== confirmPassword.value) {
        showAlert('Las contraseñas no coinciden', 'danger');
        return false;
    }
    
    return true;
}

// Validar solicitud de vacaciones
function validateVacaciones() {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    
    if (!fechaInicio || !fechaInicio.value) {
        showAlert('La fecha de inicio es obligatoria', 'danger');
        return false;
    }
    
    if (!fechaFin || !fechaFin.value) {
        showAlert('La fecha de fin es obligatoria', 'danger');
        return false;
    }
    
    if (new Date(fechaInicio.value) > new Date(fechaFin.value)) {
        showAlert('La fecha de inicio debe ser anterior a la fecha fin', 'danger');
        return false;
    }
    
    return true;
}

// Preview de imagen antes de subir
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Exportar tabla a Excel (simple)
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(function(row) {
        let cols = row.querySelectorAll('td, th');
        let rowData = [];
        
        cols.forEach(function(col) {
            rowData.push(col.innerText);
        });
        
        csv.push(rowData.join(','));
    });
    
    const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    const link = document.createElement('a');
    link.download = filename + '.csv';
    link.href = window.URL.createObjectURL(csvFile);
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
}

// Inicializar tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Animación de carga
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>';
    document.body.appendChild(spinner);
}

function hideLoading() {
    const spinner = document.querySelector('.spinner-overlay');
    if (spinner) {
        spinner.remove();
    }
}

// Añadir efectos de hover a las cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach(function(card) {
        card.style.cursor = 'pointer';
    });
});