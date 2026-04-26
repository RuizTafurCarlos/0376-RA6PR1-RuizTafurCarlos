<?php
/**
 * MONCAO SECURE - Authentication Check
 * Verifica que el usuario esté autenticado
 */

session_start();

/**
 * Verifica si el usuario está autenticado
 * Si no hay sesión, redirige al login
 */
function checkAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Verifica si el usuario tiene el rol requerido
 * @param string|array $roles Rol o array de roles permitidos
 */
function requireRole($roles) {
    checkAuth();
    
    if (!isset($_SESSION['rol'])) {
        header('Location: index.php');
        exit;
    }
    
    $rolesPermitidos = is_array($roles) ? $roles : [$roles];
    
    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        // Usuario no tiene permiso - redirigir a dashboard
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Verifica si el usuario es admin o superadmin
 */
function isAdmin() {
    return isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'superadmin']);
}

/**
 * Verifica si el usuario es superadmin
 */
function isSuperadmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'superadmin';
}

/**
 * Verifica que el usuario pertenezca al departamento requerido
 * @param int|array $departamentos ID de departamento o array de IDs
 */
function requireDepartamento($departamentos) {
    checkAuth();
    
    if (!isset($_SESSION['departamento_id'])) {
        header('Location: index.php');
        exit;
    }
    
    $depsPermitidos = is_array($departamentos) ? $departamentos : [$departamentos];
    
    if (!in_array($_SESSION['departamento_id'], $depsPermitidos)) {
        header('Location: dashboard.php');
        exit;
    }
}

// Ejecutar verificación de autenticación al incluir este archivo
checkAuth();