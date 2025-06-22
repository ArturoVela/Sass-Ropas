<?php
session_start();

// --- CONFIGURACIÓN ---
define('API_URL',   'http://ropas.spring.informaticapp.com:1688/api/ropas/usuarios');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

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
$success = '';
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
                        'empresa' => [
                            'id'     => $usr['empresa']['id'],
                            'nombre' => $usr['empresa']['nombre']
                        ]
                    ];
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
  <title>Login | Sistema de Gestión</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <!-- CSS personalizado -->
  <link rel="stylesheet" href="css/login.css">
  
  <style>
    :root {
      --primary-color: #dc3545;
      --primary-dark: #c82333;
      --secondary-color: #6c757d;
      --success-color: #28a745;
      --warning-color: #ffc107;
      --danger-color: #dc3545;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
      --gradient-primary: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      --gradient-secondary: linear-gradient(135deg, #6c757d 0%, #495057 100%);
      --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
      --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
      --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
      --border-radius: 0.75rem;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      overflow-x: hidden;
    }

    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      position: relative;
    }

    .background-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      overflow: hidden;
    }

    .floating-shapes {
      position: absolute;
      width: 100%;
      height: 100%;
    }

    .shape {
      position: absolute;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      animation: float 6s ease-in-out infinite;
    }

    .shape:nth-child(1) {
      width: 80px;
      height: 80px;
      top: 20%;
      left: 10%;
      animation-delay: 0s;
    }

    .shape:nth-child(2) {
      width: 120px;
      height: 120px;
      top: 60%;
      right: 10%;
      animation-delay: 2s;
    }

    .shape:nth-child(3) {
      width: 60px;
      height: 60px;
      bottom: 20%;
      left: 20%;
      animation-delay: 4s;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(180deg); }
    }

    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-lg);
      padding: 3rem;
      width: 100%;
      max-width: 450px;
      position: relative;
      overflow: hidden;
      transition: var(--transition);
    }

    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--gradient-primary);
    }

    .login-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, 0.2);
    }

    .brand-section {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .brand-logo {
      width: 80px;
      height: 80px;
      background: var(--gradient-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      box-shadow: var(--shadow);
      transition: var(--transition);
    }

    .brand-logo:hover {
      transform: scale(1.1);
    }

    .brand-logo i {
      font-size: 2rem;
      color: white;
    }

    .brand-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--dark-color);
      margin-bottom: 0.5rem;
    }

    .brand-subtitle {
      font-size: 0.95rem;
      color: var(--secondary-color);
      font-weight: 400;
    }

    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }

    .form-control {
      background: rgba(255, 255, 255, 0.8);
      border: 2px solid rgba(0, 0, 0, 0.1);
      border-radius: 0.75rem;
      padding: 1rem 1rem 1rem 3rem;
      font-size: 1rem;
      transition: var(--transition);
      backdrop-filter: blur(10px);
    }

    .form-control:focus {
      background: rgba(255, 255, 255, 0.95);
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
      outline: none;
    }

    .form-control::placeholder {
      color: var(--secondary-color);
      opacity: 0.7;
    }

    .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--secondary-color);
      font-size: 1.1rem;
      transition: var(--transition);
    }

    .form-control:focus + .input-icon {
      color: var(--primary-color);
    }

    .btn-login {
      background: var(--gradient-primary);
      border: none;
      border-radius: 0.75rem;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      color: white;
      width: 100%;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }

    .btn-login::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }

    .btn-login:hover::before {
      left: 100%;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 0.5rem 1.5rem rgba(220, 53, 69, 0.4);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .alert {
      border-radius: 0.75rem;
      border: none;
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
      backdrop-filter: blur(10px);
    }

    .alert-danger {
      background: rgba(220, 53, 69, 0.1);
      color: var(--danger-color);
      border-left: 4px solid var(--danger-color);
    }

    .alert-success {
      background: rgba(40, 167, 69, 0.1);
      color: var(--success-color);
      border-left: 4px solid var(--success-color);
    }

    .footer-text {
      text-align: center;
      margin-top: 2rem;
      color: var(--secondary-color);
      font-size: 0.9rem;
    }

    .footer-text a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 500;
    }

    .footer-text a:hover {
      text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .login-container {
        padding: 1rem;
      }
      
      .login-card {
        padding: 2rem;
      }
      
      .brand-title {
        font-size: 1.5rem;
      }
    }

    /* Loading animation */
    .loading {
      display: none;
    }

    .loading.active {
      display: inline-block;
    }

    .spinner {
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top: 2px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Fade in animation */
    .fade-in {
      animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>
  <div class="background-animation">
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>
  </div>

  <div class="login-container">
    <div class="login-card fade-in">
      <div class="brand-section">
        <div class="brand-logo">
          <i class="fas fa-store"></i>
        </div>
        <h1 class="brand-title">Sistema de Gestión</h1>
        <p class="brand-subtitle">Accede a tu panel administrativo</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger fade-in">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success fade-in">
          <i class="fas fa-check-circle me-2"></i>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="needs-validation" novalidate>
        <div class="form-group">
          <input
            type="email"
            name="email"
            class="form-control"
            placeholder="Correo electrónico"
            required
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          <i class="fas fa-envelope input-icon"></i>
          <div class="invalid-feedback">
            Por favor ingresa un correo válido.
          </div>
        </div>

        <div class="form-group">
          <input
            type="password"
            name="password"
            class="form-control"
            placeholder="Contraseña"
            required>
          <i class="fas fa-lock input-icon"></i>
          <div class="invalid-feedback">
            Por favor ingresa tu contraseña.
          </div>
        </div>

        <button type="submit" class="btn btn-login" id="loginBtn">
          <span class="btn-text">Iniciar Sesión</span>
          <span class="loading">
            <div class="spinner"></div>
          </span>
        </button>
      </form>

      <div class="footer-text">
        <p>&copy; 2024 Sistema de Gestión. Todos los derechos reservados.</p>
        <p>Desarrollado con <i class="fas fa-heart text-danger"></i> para tu negocio</p>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
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
          } else {
            // Mostrar loading
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            
            btnText.style.display = 'none';
            loading.classList.add('active');
            btn.disabled = true;
          }
        });
      });
    })();

    // Animación de entrada
    document.addEventListener('DOMContentLoaded', function() {
      const card = document.querySelector('.login-card');
      card.style.opacity = '0';
      card.style.transform = 'translateY(30px)';
      
      setTimeout(() => {
        card.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, 100);
    });

    // Efecto de hover en inputs
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });

    // Parallax effect en shapes
    window.addEventListener('scroll', function() {
      const scrolled = window.pageYOffset;
      const shapes = document.querySelectorAll('.shape');
      
      shapes.forEach((shape, index) => {
        const speed = 0.5 + (index * 0.1);
        shape.style.transform = `translateY(${scrolled * speed}px)`;
      });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
      setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease-out';
        alert.style.opacity = '0';
        setTimeout(() => {
          alert.remove();
        }, 500);
      }, 5000);
    });
  </script>
</body>
</html>
