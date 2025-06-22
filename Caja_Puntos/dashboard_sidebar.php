<?php
$user    = $_SESSION['user'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);
$current = basename($_SERVER['SCRIPT_NAME']);
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
        $r = max(0, hexdec(substr($hex, 0, 2)) - round(hexdec(substr($hex, 0, 2)) * ($percent / 100)));
        $g = max(0, hexdec(substr($hex, 2, 2)) - round(hexdec(substr($hex, 2, 2)) * ($percent / 100)));
        $b = max(0, hexdec(substr($hex, 4, 2)) - round(hexdec(substr($hex, 4, 2)) * ($percent / 100)));
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}

$brandColor = $colorMap[$empId] ?? '#dc3545'; // Default to red if ID not in map
$hoverColor = darken_color($brandColor);


// --- Obtener nombre de la sucursal seleccionada ---
$nombreSucursalSeleccionada = '';
if (isset($_SESSION['sucursal_seleccionada'])) {
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
<style>
/* Custom sidebar styles with a dynamic theme - NON-COLLAPSIBLE */
:root {
    --sidebar-bg: <?= $brandColor ?>;
    --sidebar-text: rgba(255, 255, 255, 0.85);
    --sidebar-text-active: #ffffff;
    --sidebar-header-text: #ffffff;
    --sidebar-hover-bg: <?= $hoverColor ?>;
    --sidebar-active-border: #ffffff;
}

#sidebarOffcanvas {
  background-color: var(--sidebar-bg) !important;
}
.sidebar-brand {
    font-size: 1.25rem;
    font-weight: bold;
    padding: 1.5rem 1rem;
    color: var(--sidebar-text-active);
    text-align: center;
}
.sidebar-sucursal {
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--sidebar-hover-bg);
    margin-bottom: 1rem;
}
.sidebar-sucursal .badge {
    background-color: var(--sidebar-text-active) !important;
    color: var(--sidebar-bg) !important;
    font-weight: 500;
}
.sidebar-nav {
    padding: 0;
    list-style: none;
}
.sidebar-header {
    padding: 1.2rem 1.5rem 0.5rem 1.5rem;
    font-size: 0.7rem;
    color: var(--sidebar-header-text);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 700;
    opacity: 0.8;
}
.sidebar-nav li {
    list-style-type: none !important; /* Ensure no bullets ever */
}
.sidebar-nav li a {
    color: var(--sidebar-text);
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 0.7rem 1.5rem;
    transition: all 0.2s ease-in-out;
    border-radius: 4px;
    margin: 0.1rem 0.5rem;
    border-left: 4px solid transparent;
}
.sidebar-nav li a:hover {
    background-color: var(--sidebar-hover-bg);
    color: var(--sidebar-text-active);
}
.sidebar-nav li a.active {
    background-color: var(--sidebar-hover-bg);
    color: var(--sidebar-text-active);
    border-left-color: var(--sidebar-active-border);
    font-weight: 500;
}
.sidebar-nav li a .bi {
    margin-right: 1rem;
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}
.sidebar-footer {
    margin-top: auto;
    padding: 1rem;
    border-top: 1px solid var(--sidebar-hover-bg);
}
.sidebar-footer a {
    background: transparent;
    border: 1px solid var(--sidebar-text-active);
    color: var(--sidebar-text-active);
}
.sidebar-footer a:hover {
    background: var(--sidebar-text-active);
    color: var(--sidebar-bg);
}
.navbar.d-md-none {
    background-color: var(--sidebar-bg) !important;
}
</style>
<!-- NAVBAR MÓVIL -->
<nav class="navbar d-md-none">
  <div class="container-fluid">
    <button class="btn btn-dark" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
      <i class="bi bi-list fs-4"></i>
    </button>
    <span class="navbar-brand ms-2"><?= $empName ?></span>
  </div>
</nav>

<!-- SIDEBAR / OFFCANVAS -->
<div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarOffcanvas">
  <div class="offcanvas-header d-md-none">
    <h5 class="offcanvas-title"><?= $empName ?></h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
  </div>

  <div class="offcanvas-body d-flex flex-column p-0">
    <div class="sidebar-brand">
        <?= $empName ?>
    </div>
    <?php if ($nombreSucursalSeleccionada): ?>
      <div class="sidebar-sucursal">
        <span class="badge px-3 py-2">
          <i class="bi bi-building me-1"></i><?= htmlspecialchars($nombreSucursalSeleccionada, ENT_QUOTES) ?>
        </span>
      </div>
    <?php endif; ?>

    <ul class="sidebar-nav flex-grow-1">
        <li class="sidebar-header">Home</li>
        <li><a href="dashboard.php" class="nav-link <?= $current==='dashboard.php' ? 'active' : '' ?>"><i class="bi bi-house-fill"></i><span>Inicio</span></a></li>
        
        <li class="sidebar-header">Puntos</li>
        <li><a href="Puntosclientes.php" class="nav-link <?= $current==='Puntosclientes.php' ? 'active' : '' ?>"><i class="bi bi-people-fill"></i>Puntos Clientes</a></li>
        <li><a href="historial_puntos.php" class="nav-link <?= $current==='historial_puntos.php' ? 'active' : '' ?>"><i class="bi bi-clock-history"></i>Historial</a></li>
        <li><a href="recompensas.php" class="nav-link <?= $current==='recompensas.php' ? 'active' : '' ?>"><i class="bi bi-gift-fill"></i>Recompensas</a></li>
        <li><a href="canjes.php" class="nav-link <?= $current==='canjes.php' ? 'active' : '' ?>"><i class="bi bi-arrow-left-right"></i>Canjes</a></li>
        
        <li class="sidebar-header">Caja</li>
        <li><a href="caja.php" class="nav-link <?= $current==='caja.php' ? 'active' : '' ?>"><i class="bi bi-cash-stack"></i>Administrar Caja</a></li>
        <li><a href="movimientos_caja.php" class="nav-link <?= $current==='movimientos_caja.php' ? 'active' : '' ?>"><i class="bi bi-arrow-repeat"></i>Movimientos</a></li>
    </ul>

    <div class="sidebar-footer">
      <a href="../dashboard.php" class="btn w-100">
        <i class="bi bi-arrow-left-circle me-2"></i>Volver
      </a>
    </div>
  </div>
</div>
