<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user    = $_SESSION['user'] ?? [];
$empId   = $user['empresa']['id'] ?? 1; // Default to ID 1 if not set

// --- Color map based on empresa ID ---
$colorMap = [
    1 => '#FF5733',  // Rojo
    2 => '#FFA500',  // Naranja
    3 => '#FFD700',  // Amarillo
    4 => '#32CD32',  // Verde lima
    5 => '#228B22',  // Verde
    6 => '#00CED1',  // Cian
    7 => '#1E90FF',  // Azul claro
    8 => '#4169E1',  // Azul
    9 => '#8A2BE2',  // Violeta
    10 => '#FF69B4', // Rosa
    11 => '#FF00FF', // Magenta
    12 => '#8B4513', // Marrón
    13 => '#708090', // Gris
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