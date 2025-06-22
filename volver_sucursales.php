<?php
session_start();

// Limpiar la variable de sesión de la sucursal actual
if (isset($_SESSION['sucursal_id'])) {
    unset($_SESSION['sucursal_id']);
}

// Redirigir de vuelta a la lista de sucursales
header('Location: sucursales.php');
exit; 