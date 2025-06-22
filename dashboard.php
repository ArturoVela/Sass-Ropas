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
// --- CONFIGURACIÓN API ---
define('API_BASE',  'http://ropas.spring.informaticapp.com:1688/api/ropas');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM'); // reemplaza con tu token

function fetchSucursales(): array
{
  $ch = curl_init(API_BASE . '/sucursales');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . API_TOKEN],
  ]);
  $raw = curl_exec($ch);
  curl_close($ch);
  return json_decode($raw, true) ?: [];
}

function fetchUsuarios(): array
{
  $ch = curl_init(API_BASE . '/usuarios');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . API_TOKEN],
  ]);
  $raw = curl_exec($ch);
  curl_close($ch);
  return json_decode($raw, true) ?: [];
}

// Filtrar sólo los de esta empresa
$sucursales = array_filter(fetchSucursales(), fn($s) => $s['empresa']['id'] === $empId);
$empleados  = array_filter(fetchUsuarios(),   fn($u) => isset($u['empresa']['id']) && $u['empresa']['id'] === $empId);

// Conteos
$sucCount = count($sucursales);
$empCount = count($empleados);

// Agrupar empleados por sucursal
$empBySuc = [];
foreach ($empleados as $u) {
  $sid = $u['sucursal']['id'] ?? null;
  if ($sid) $empBySuc[$sid][] = $u;
}

// Para determinar activo en el sidebar
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Dashboard | <?= htmlspecialchars($empName) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="css/dashboard.css">
  
  <style>
    :root { --header-height: 70px; --sidebar-width: 250px; --primary-color: #dc3545; --secondary-color: #6c757d; --light-color: #f8f9fa; --dark-color: #343a40; --border-color: #dee2e6; --shadow: 0 0.125rem 0.25rem rgba(0,0,0, .075); --shadow-lg: 0 0.5rem 1rem rgba(0,0,0, .15); --transition: all 0.3s ease; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background-color: #f8f9fa; }
    .main-header { position: fixed; top: 0; left: var(--sidebar-width); right: 0; height: var(--header-height); background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-color); z-index: 1030; transition: var(--transition); box-shadow: var(--shadow); }
    .header-content { display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 0 2rem; }
    .page-title { font-size: 1.5rem; font-weight: 600; color: var(--dark-color); margin: 0; }
    .user-menu { position: relative; }
    .user-menu-toggle { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 1rem; background: transparent; border: 1px solid var(--border-color); border-radius: 0.75rem; cursor: pointer; transition: var(--transition); text-decoration: none; color: var(--dark-color); }
    .user-menu-toggle:hover { background: var(--light-color); border-color: var(--primary-color); color: var(--primary-color); }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #dc3545, #c82333); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1rem; }
    .user-info { text-align: left; }
    .user-name { font-weight: 600; font-size: 0.9rem; margin: 0; }
    .user-role { font-size: 0.75rem; color: var(--secondary-color); }
    .user-menu-dropdown { position: absolute; top: 100%; right: 0; width: 220px; background: white; border: 1px solid var(--border-color); border-radius: 0.75rem; box-shadow: var(--shadow-lg); opacity: 0; visibility: hidden; transform: translateY(-10px); transition: var(--transition); z-index: 1000; margin-top: 0.5rem; }
    .user-menu-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
    .dropdown-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); }
    .dropdown-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: var(--dark-color); text-decoration: none; }
    .dropdown-item:hover { background: var(--light-color); color: var(--primary-color); }
    .dropdown-divider { height: 1px; background: var(--border-color); margin: 0.5rem 0; }
    .main-content { margin-top: var(--header-height); margin-left: var(--sidebar-width); padding: 2rem; min-height: calc(100vh - var(--header-height)); }
    @media (min-width: 768px) {

/* Fija el offcanvas al lado izquierdo */
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

/* Hace que el body del sidebar ocupe toda la altura */
#sidebarOffcanvas .offcanvas-body {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

/* Mueve el contenido principal */
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
      <h1 class="page-title">Dashboard</h1>
      <div class="user-menu">
        <button class="user-menu-toggle" onclick="toggleUserMenu()">
          <div class="user-avatar"><?= strtoupper(substr($user['nombre'], 0, 1)) ?></div>
          <div class="user-info">
            <p class="user-name mb-0"><?= htmlspecialchars($user['nombre']) ?></p>
            <p class="user-role mb-0"><?= htmlspecialchars($user['rol']) ?></p>
          </div>
          <i class="fas fa-chevron-down"></i>
        </button>
        <div class="user-menu-dropdown" id="userMenuDropdown">
          <div class="dropdown-header">
            <h6><?= htmlspecialchars($user['nombre']) ?></h6>
            <p class="text-muted mb-0"><?= htmlspecialchars($empName) ?></p>
          </div>
          <a href="configuracion.php" class="dropdown-item"><i class="fas fa-cog"></i><span>Configuración</span></a>
          <a href="#" class="dropdown-item"><i class="fas fa-question-circle"></i><span>Ayuda</span></a>
          <div class="dropdown-divider"></div>
          <a href="logout.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></a>
        </div>
      </div>
    </div>
  </header>

  <main class="main-content">
    <div class="container-fluid">
      <div class="row mb-4">
        <div class="col-12">
          <h2 class="h3 text-dark mb-3">Bienvenido, <?= htmlspecialchars($user['nombre'], ENT_QUOTES) ?></h2>
          <p class="text-muted">Aquí tienes un resumen de tu empresa y las estadísticas principales.</p>
        </div>
      </div>
      <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3"><div class="metric-card card h-100"><div class="card-body text-center"><h5 class="card-title text-danger">Sucursales</h5><p class="display-4 text-danger"><?= $sucCount ?></p></div></div></div>
        <div class="col-lg-3 col-md-6 mb-3"><div class="metric-card card h-100"><div class="card-body text-center"><h5 class="card-title text-danger">Empleados</h5><p class="display-4 text-danger"><?= $empCount ?></p></div></div></div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function toggleUserMenu() {
      document.getElementById('userMenuDropdown').classList.toggle('show');
    }
    document.addEventListener('click', function(event) {
      if (!event.target.closest('.user-menu')) {
        document.getElementById('userMenuDropdown').classList.remove('show');
      }
    });
  </script>
</body>
</html>