<?php
session_start();

// --- CONFIGURACIÓN ---
define('API_URL',   'http://ropas.spring.informaticapp.com:1644/api/ropas/usuarios');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM'); // tu token

function fetchUsuarios(): array {
    $ch = curl_init(API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Bearer ' . API_TOKEN
        ],
    ]);
    $raw = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('Error en conexión: ' . curl_error($ch));
    }
    curl_close($ch);
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al parsear JSON: ' . json_last_error_msg());
    }
    return $data;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    if (!$email || $password === '') {
        $error = 'Por favor ingresa un correo y contraseña válidos.';
    } else {
        try {
            $usuarios = fetchUsuarios();
            foreach ($usuarios as $usr) {
                if ($usr['correo'] === $email
                    && $usr['password'] === $password
                    && intval($usr['estado']) === 1
                ) {
                    // Almacenar en sesión
                    $_SESSION['user'] = [
                        'id'      => $usr['id'],
                        'nombre'  => $usr['nombre'],
                        'rol'     => $usr['rol'],
                        'empresa' => $usr['empresa']
                    ];

                    // --- Registrar auditoría de Login ---
                    if ($usr['rol'] !== 'SUPERadmin') {
                        $ch_audit = curl_init('http://ropas.spring.informaticapp.com:1644/api/ropas/auditoria');
                        $payload = json_encode([
                            'usuario' => ['id' => $usr['id']],
                            'evento' => 'INICIO DE SESIÓN',
                            'descripcion' => 'El usuario ha iniciado sesión en el sistema.',
                        ]);
                        curl_setopt_array($ch_audit, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => $payload,
                            CURLOPT_HTTPHEADER => [
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . API_TOKEN
                            ],
                        ]);
                        curl_exec($ch_audit);
                        curl_close($ch_audit);
                    }

                    // Redirigir según rol
                    if ($usr['rol'] === 'SUPERadmin') {
                        header('Location: superadmin_dashboard.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                }
            }
            $error = 'Credenciales incorrectas o usuario inactivo.';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login | <?= htmlspecialchars($_SESSION['user']['empresa']['nombre'] ?? '') ?></title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- tsParticles -->
  <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.11.1/tsparticles.min.js"></script>
  <!-- Google Font Montserrat -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet"/>
  <!-- CSS personalizado -->
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <!-- Partículas -->
  <div id="tsparticles" class="position-fixed top-0 start-0 w-100 h-100"></div>
  <!-- Emoji rain -->
  <div id="emojiRain"></div>

  <div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card login-card shadow-sm">
      <div class="card-body">
        <h2 class="card-title text-center text-danger mb-4"><strong>Login/Panel Administrativo</strong></h2>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="needs-validation" novalidate>
          <div class="mb-3">
            <input
              type="email"
              name="email"
              class="form-control"
              placeholder="Correo"
              required
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <div class="invalid-feedback">Ingresa un correo válido.</div>
          </div>
          <div class="mb-3">
            <input
              type="password"
              name="password"
              class="form-control"
              placeholder="Contraseña"
              required>
            <div class="invalid-feedback">Ingresa tu contraseña.</div>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-danger btn-lg">Ingresar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS y validación -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Validación Bootstrap
    (() => {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', e => {
          if (!form.checkValidity()) {
            e.preventDefault();
            form.classList.add('was-validated');
          }
        });
      });
    })();
  </script>

  <!-- tsParticles init -->
  <script>
    tsParticles.load("tsparticles", {
      background: { opacity: 0 },
      fpsLimit: 60,
      particles: {
        color: { value: "#ffffff" },
        links: { enable: true, color: "#ffffff", distance: 150, opacity: .2, width: 1 },
        move: { enable: true, speed: 2 },
        number: { density: { enable: true, area: 800 }, value: 80 },
        size: { value: { min: 1, max: 3 } }
      },
      detectRetina: true
    });
  </script>

  <!-- Emoji rain -->
  <script>
    const emojis = ['👕','👖','👗','🧥','👔','👓','🕶️','🥼','🦺','🧣','🧤','🧦','👙','👑'];
    const rain    = document.getElementById('emojiRain');
    function dropEmoji(){
      const span = document.createElement('span');
      span.className = 'rain-emoji';
      span.innerText = emojis[Math.floor(Math.random()*emojis.length)];
      const x = Math.random()*100;
      const dur = 4+Math.random()*4;
      const size= 16+Math.random()*24;
      span.style.left = x+'%';
      span.style.fontSize = size+'px';
      span.style.animationDuration = dur+'s';
      rain.appendChild(span);
      setTimeout(()=> rain.removeChild(span), dur*1000);
    }
    setInterval(dropEmoji,300);
  </script>

  <!-- Detección de zoom y scroll -->
  <script>
    function detectZoom(){
      const zoomPct = Math.round((window.outerWidth/window.innerWidth)*100);
      if(zoomPct>=300){
        document.documentElement.classList.add('zoom-scroll');
        document.body.classList.add('zoom-scroll');
      } else {
        document.documentElement.classList.remove('zoom-scroll');
        document.body.classList.remove('zoom-scroll');
      }
    }
    window.addEventListener('load', detectZoom);
    window.addEventListener('resize', detectZoom);
    setInterval(detectZoom,1000);
  </script>
</body>
</html>
