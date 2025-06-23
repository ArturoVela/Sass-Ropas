<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user    = $_SESSION['user'] ?? [];
$empId   = $user['empresa']['id'] ?? 1; // Default to ID 1 if not set

// --- Theme color map ---
$themeColors = [
    1 => ['name' => 'Rojo Vino', 'hex' => '#A93226'],
    2 => ['name' => 'Naranja Quemado', 'hex' => '#D35400'],
    3 => ['name' => 'Dorado', 'hex' => '#B7950B'],
    4 => ['name' => 'Esmeralda', 'hex' => '#27AE60'],
    5 => ['name' => 'Verde Oscuro', 'hex' => '#196F3D'],
    6 => ['name' => 'Turquesa', 'hex' => '#117A65'],
    7 => ['name' => 'Azul Acero', 'hex' => '#2980B9'],
    8 => ['name' => 'Azul Profundo', 'hex' => '#1F618D'],
    9 => ['name' => 'Púrpura', 'hex' => '#6C3483'],
    10 => ['name' => 'Lavanda', 'hex' => '#AF7AC5'],
    11 => ['name' => 'Berenjena', 'hex' => '#884EA0'],
    12 => ['name' => 'Chocolate', 'hex' => '#6E2C00'],
    13 => ['name' => 'Gris Acero', 'hex' => '#566573'],
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

// Get default theme based on company ID
$defaultTheme = $themeColors[$empId] ?? ['name' => 'Rojo Defecto', 'hex' => '#A93226'];
$brandColor = $defaultTheme['hex'];
$hoverColor = darken_color($brandColor);

?> 