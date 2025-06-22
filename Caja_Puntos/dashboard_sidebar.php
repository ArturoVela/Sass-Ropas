<?php
$user    = $_SESSION['user'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);
$current = basename($_SERVER['SCRIPT_NAME']);

// --- Obtener nombre de la sucursal seleccionada ---
$nombreSucursalSeleccionada = '';
if (isset($_SESSION['sucursal_seleccionada'])) {
    // Obtener sucursales para encontrar el nombre
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/sucursales',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM'
      ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    
    $sucursalesCompletas = json_decode($response, true);
    if (is_array($sucursalesCompletas)) {
        foreach ($sucursalesCompletas as $sucursal) {
            if ($sucursal['id'] == $_SESSION['sucursal_seleccionada']) {
                $nombreSucursalSeleccionada = $sucursal['nombre'];
                break;
            }
        }
    }
}
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
<div class="offcanvas offcanvas-start bg-danger text-white" tabindex="-1" id="sidebarOffcanvas">
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
    <div class="d-none d-md-block text-center py-4">
      <h5 class="fw-bold"><?= $empName ?></h5>
      <?php if ($nombreSucursalSeleccionada): ?>
        <div class="mt-2">
          <span class="badge bg-light text-danger px-3 py-2">
            <i class="bi bi-building me-1"></i><?= htmlspecialchars($nombreSucursalSeleccionada, ENT_QUOTES) ?>
          </span>
        </div>
      <?php endif; ?>
    </div>

    <div class="d-grid gap-2 px-3">

    <!-- Separador -->
      <div class="d-flex align-items-center justify-content-center my-4">
  <div style="flex: 1; height: 2px; background: white; max-width: 200px;"></div>
  <span class="px-3 text-uppercase fw-bold" style="letter-spacing: 2px;">Home</span>
  <div style="flex: 1; height: 2px; background: white; max-width: 200px;"></div>
</div>


      <a href="dashboard.php" class="btn <?= $current==='dashboard.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>">
        <i class="bi bi-house-fill me-2"></i>Inicio
      </a>

      <div class="d-flex align-items-center justify-content-center my-4">
  <div style="flex: 1; height: 2px; background: white; max-width: 200px;"></div>
  <span class="px-3 text-uppercase fw-bold" style="letter-spacing: 2px;">Puntos</span>
  <div style="flex: 1; height: 2px; background: white; max-width: 200px;"></div>
</div>

      <a href="Puntosclientes.php" class="btn <?= $current==='Puntosclientes.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>">
        <i class="bi bi-people-fill me-2"></i>Puntos Clientes
      </a>
      <a href="historial_puntos.php" class="btn <?= $current==='historial_puntos.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>">
        <i class="bi bi-clock-history me-2"></i>Historial Puntos
      </a>
      <a href="recompensas.php" class="btn <?= $current==='recompensas.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>">
        <i class="bi bi-gift-fill me-2"></i>Recompensas
      </a>
      <a href="canjes.php" class="btn <?= $current==='canjes.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>">
        <i class="bi bi-arrow-left-right me-2"></i>Canjes
      </a>

      <div class="d-flex align-items-center justify-content-center my-4">
  <div style="flex: 1; height: 2px; background: white; max-width: 200px;"></div>
  <span class="px-3 text-uppercase fw-bold" style="letter-spacing: 2px;">Caja</span>
  <div style="flex: 1; height: 2px; background: white; max-width: 200px;"></div>
</div>

      <a href="caja.php" class="btn <?= $current==='caja.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>">
        <i class="bi bi-cash-stack me-2"></i>Caja
      </a>
      <a href="movimientos_caja.php" class="btn <?= $current==='movimientos_caja.php' ? 'btn-light text-danger' : 'btn-outline-light' ?>">
        <i class="bi bi-arrow-repeat me-2"></i>Movimientos Caja
      </a>
    </div>

    <div class="mt-auto px-3" style="padding-bottom: 10px;">
      <a href="logout.php" class="btn btn-outline-light w-100">
        <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
      </a>
    </div>
  </div>
</div>
