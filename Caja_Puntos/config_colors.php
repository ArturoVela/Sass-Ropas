<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user    = $_SESSION['user'] ?? [];
$empId   = $user['empresa']['id'] ?? 1; // Default to ID 1 if not set

// --- Color map based on empresa ID ---
$colorMap = [
    1 => '#A93226',  // Rojo vino
    2 => '#D35400',  // Naranja quemado
    3 => '#B7950B',  // Mostaza dorado
    4 => '#27AE60',  // Verde esmeralda
    5 => '#196F3D',  // Verde oscuro
    6 => '#117A65',  // Verde azulado profundo
    7 => '#2980B9',  // Azul acero
    8 => '#1F618D',  // Azul profundo
    9 => '#6C3483',  // Púrpura elegante
    10 => '#AF7AC5', // Lavanda sofisticado
    11 => '#884EA0', // Morado berenjena
    12 => '#6E2C00', // Marrón chocolate oscuro
    13 => '#566573', // Gris acero azulado
];


// Helper function to darken a color for hover effects
if (!function_exists('darken_color')) {
    function darken_color($hex, $percent = 10) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = str_repeat($hex[0], 2) . str_repeat($hex[1], 2) . str_repeat($hex[2], 2);
        }
        if (strlen($hex) != 6) return '#000000'; // return a default if hex is invalid
        $r = max(0, hexdec(substr($hex, 0, 2)) - round(hexdec(substr($hex, 0, 2)) * ($percent / 100)));
        $g = max(0, hexdec(substr($hex, 2, 2)) - round(hexdec(substr($hex, 2, 2)) * ($percent / 100)));
        $b = max(0, hexdec(substr($hex, 4, 2)) - round(hexdec(substr($hex, 4, 2)) * ($percent / 100)));
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}

$brandColor = $colorMap[$empId] ?? '#dc3545'; // Default to red if ID not in map
$hoverColor = darken_color($brandColor);

?> 