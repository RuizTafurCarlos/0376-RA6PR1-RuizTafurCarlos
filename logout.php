<?php
/**
 * MONCAO SECURE - Logout
 * Cerrar sesión
 */

session_start();

// Destruir sesión
$_SESSION = array();
session_destroy();

// Redirigir al login
header('Location: index.php');
exit;