<?php
session_start();
if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header('Location: sucursales.php');
    exit;
}

// Guardar el ID de la sucursal en la sesión para usarlo en la página de detalle
$_SESSION['sucursal_seleccionada'] = (int)$_GET['id'];

// Redirigir a la página de detalle de la sucursal
header('Location: Caja_Puntos/dashboard.php');
exit; 