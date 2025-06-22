<?php
session_start();

// Verificar si existe una sesión original de SUPERadmin guardada
if (isset($_SESSION['superadmin_original_user'])) {
    
    // Restaurar la sesión del SUPERadmin
    $_SESSION['user'] = $_SESSION['superadmin_original_user'];
    
    // Eliminar la sesión guardada para limpiar
    unset($_SESSION['superadmin_original_user']);
    
    // Redirigir al panel de empresas
    header('Location: empresas.php');
    exit;
    
} else {
    
    // Si no hay sesión que restaurar, redirigir al dashboard principal o al login
    header('Location: dashboard.php');
    exit;
} 