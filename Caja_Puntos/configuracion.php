<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

$user    = $_SESSION['user'];
$empId   = $user['empresa']['id'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);

define('API_BASE',  'http://ropas.spring.informaticapp.com:1644/api/ropas');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

function fetchUsuarios(): array {
  $ch = curl_init(API_BASE . '/usuarios');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . API_TOKEN],
  ]);
  $raw = curl_exec($ch);
  curl_close($ch);
  return json_decode($raw, true) ?: [];
}

// --- MANEJO DE FORMULARIO DE CAMBIO DE CONTRASEÑA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'];
    $newPassword     = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        header('Location: configuracion.php?password_changed=error_mismatch');
        exit;
    }

    $allUsers = fetchUsuarios();
    $currentUserData = null;
    foreach ($allUsers as $u) {
        if ($u['id'] === $user['id']) {
            $currentUserData = $u;
            break;
        }
    }

    if ($currentUserData && isset($currentUserData['password']) && $currentUserData['password'] === $currentPassword) {
        $postData = '{
            "id": '.$currentUserData['id'].',
            "empresa": '.$currentUserData['empresa']['id'].',
            "nombre": "'.$currentUserData['nombre'].'",
            "correo": "'.$currentUserData['correo'].'",
            "password": "'.$newPassword.'",
            "rol": "'.$currentUserData['rol'].'",
            "estado": '.$currentUserData['estado'].'
        }';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => API_BASE . '/usuarios',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . API_TOKEN],
        ]);
        curl_exec($curl);
        curl_close($curl);
        
        $fecha_actual = date('Y-m-d\TH:i:s');
        $auditCurl = curl_init();
        curl_setopt_array($auditCurl, [
          CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/auditoria',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => '{"usuario": {"id":'.$user['id'].'},"sucursal": {"id":1},"evento": "CAMBIO DE CONTRASEÑA","descripcion": "El usuario '.$user['nombre'].' ha cambiado su contraseña.","fecha": "'.$fecha_actual.'","estado": 1}',
          CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . API_TOKEN],
        ]);
        curl_exec($auditCurl);
        curl_close($auditCurl);

        header('Location: configuracion.php?password_changed=success');
        exit;
    } else {
        header('Location: configuracion.php?password_changed=error_current');
        exit;
    }
}

$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Configuración | <?= htmlspecialchars($empName) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/dashboard.css">
  <style>
    :root { --header-height: 70px; --sidebar-width: 250px; --primary-color: #dc3545; --secondary-color: #6c757d; --light-color: #f8f9fa; --dark-color: #343a40; --border-color: #dee2e6; --shadow: 0 0.125rem 0.25rem rgba(0,0,0, .075); --transition: all 0.3s ease; }
    body { background-color: #f8f9fa; }
    .main-header { position: fixed; top: 0; left: var(--sidebar-width); right: 0; height: var(--header-height); background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-color); z-index: 1030; transition: var(--transition); }
    .header-content { display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 0 2rem; }
    .page-title { font-size: 1.5rem; font-weight: 600; color: var(--dark-color); margin: 0; }
    .main-content { margin-top: var(--header-height); margin-left: var(--sidebar-width); padding: 2rem; min-height: calc(100vh - var(--header-height)); }
    @media (min-width: 768px) {
      #sidebarOffcanvas.offcanvas-start {
        position: fixed !important;
        top: 0;
        left: 0;
        bottom: 0;
        width: 250px;
        transform: none !important;
        visibility: visible !important;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        z-index: 1020;
      }
      #sidebarOffcanvas .offcanvas-body {
        display: flex;
        flex-direction: column;
        height: 100vh;
      }
      .content {
        margin-left: 250px;
      }
    }
  </style>
</head>
<body class="bg-light">

  <?php include 'dashboard_sidebar.php'; ?>

  <header class="main-header">
    <div class="header-content">
      <h1 class="page-title">Configuración de Usuario</h1>
    </div>
  </header>

  <div class="main-content">
    <div class="container-fluid">
      <?php if (isset($_GET['password_changed'])): ?>
        <div class="alert alert-<?= $_GET['password_changed'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?php
            if ($_GET['password_changed'] === 'success') echo '<strong>¡Éxito!</strong> Su contraseña ha sido cambiada correctamente.';
            elseif ($_GET['password_changed'] === 'error_mismatch') echo '<strong>Error:</strong> La nueva contraseña y la confirmación no coinciden.';
            else echo '<strong>Error:</strong> La contraseña actual es incorrecta.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-lg-5 col-md-12 mb-4">
          <div class="card h-100">
            <div class="card-header d-flex align-items-center"><i class="fas fa-user-circle me-2"></i><h5 class="mb-0">Mi Perfil</h5></div>
            <div class="card-body">
              <div class="text-center mb-4">
                <div class="user-avatar" style="width: 100px; height: 100px; font-size: 2.5rem; margin: auto; background: var(--primary-color); color: white; border-radius: 50%; display:flex; align-items:center; justify-content:center;"><?= strtoupper(substr($user['nombre'], 0, 1)) ?></div>
                <h4 class="mt-3 mb-0"><?= htmlspecialchars($user['nombre']) ?></h4>
                <span class="badge bg-primary mt-1"><?= htmlspecialchars($user['rol']) ?></span>
              </div>
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><strong>ID de Usuario:</strong> <span><?= $user['id'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><strong>Correo:</strong> <span><?= htmlspecialchars($user['correo'] ?? 'No disponible') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><strong>Empresa:</strong> <span><?= htmlspecialchars($empName) ?> (ID: <?= $empId ?>)</span></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-lg-7 col-md-12 mb-4">
          <div class="card h-100">
            <div class="card-header d-flex align-items-center"><i class="fas fa-key me-2"></i><h5 class="mb-0">Cambiar Contraseña</h5></div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="mb-3">
                  <label for="current_password" class="form-label">Contraseña Actual</label>
                  <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                  <label for="new_password" class="form-label">Nueva Contraseña</label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="mb-3">
                  <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 