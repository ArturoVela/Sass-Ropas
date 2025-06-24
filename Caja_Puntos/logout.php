<?php
// logout.php
session_start();

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
header('Location: ../index.php');
exit;
