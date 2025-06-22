<?php
// logout.php
session_start();

// --- Registrar auditoría ANTES de destruir la sesión ---
if (isset($_SESSION['user']) && $_SESSION['user']['rol'] !== 'SUPERadmin') {
    $ch = curl_init('http://ropas.spring.informaticapp.com:1655/api/ropas/auditoria');
    $payload = json_encode([
        'usuario' => ['id' => $_SESSION['user']['id']],
        'evento' => 'CIERRE DE SESIÓN',
        'descripcion' => 'El usuario cerró su sesión en el sistema.',
    ]);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM'
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// 1) Limpia todas las variables de sesión
$_SESSION = [];

// 2) Destruye la cookie de sesión en el navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3) Finalmente destruye la sesión en el servidor
session_destroy();

// 4) Redirige al login (o a index.php según tu estructura)
header('Location: index.php');
exit;
