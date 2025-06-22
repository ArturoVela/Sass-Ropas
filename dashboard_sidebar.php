<?php
// dashboard_sidebar.php
// Inclúyelo justo al abrir <body> en tus vistas de dashboard
$user    = $_SESSION['user'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!-- NAVBAR MÓVIL -->
<nav class="navbar navbar-dark bg-danger d-md-none">
  <div class="container-fluid">
    <button
      class="btn btn-danger"
      type="button"
      data-bs-toggle="offcanvas"
      data-bs-target="#sidebarOffcanvas"
    >
      <i class="bi bi-list fs-4"></i>
    </button>
    <span class="navbar-brand ms-2"><?= $empName ?></span>
  </div>
</nav>

<!-- SIDEBAR / OFFCANVAS -->
<div
  class="offcanvas-md offcanvas-start bg-danger text-white"
  tabindex="-1"
  id="sidebarOffcanvas"
>
  <div class="offcanvas-header d-md-none">
    <h5 class="offcanvas-title"><?= $empName ?></h5>
    <button
      type="button"
      class="btn-close btn-close-white"
      data-bs-dismiss="offcanvas"
      aria-label="Cerrar"
    ></button>
  </div>

  <div class="offcanvas-body d-flex flex-column p-3 p-md-0">
    <!-- Branding en md+ -->
    <div class="d-none d-md-block text-center py-4">
      <h5 class="fw-bold"><?= $empName ?></h5>
    </div>

    <!-- Botones de navegación -->
    <div class="d-grid gap-2 px-3">
      <a
        href="dashboard.php"
        class="btn <?= $current==='dashboard.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>"
      >
        <i class="bi bi-house me-2"></i> Inicio
      </a>
      <a
        href="sucursales.php"
        class="btn <?= $current==='sucursales.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>"
      >
        <i class="bi bi-geo-alt-fill me-2"></i> Sucursales
      </a>
    </div>

    <!-- Empuja el logout abajo -->
    <div class="mt-auto px-3">
      <a href="logout.php" class="btn btn-outline-light w-100">
        <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
      </a>
    </div>
  </div>
</div>

<!-- Reserva espacio para el sidebar en md+ -->
<div class="d-none d-md-block" style="width:250px;"></div>
