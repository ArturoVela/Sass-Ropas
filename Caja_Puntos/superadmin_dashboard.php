<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'SUPERadmin') {
    header('Location: index.php');
    exit;
}

// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

// --- API de Empresas y Auditorías ---
define('API_EMPRESAS',   'http://ropas.spring.informaticapp.com:1644/api/ropas/empresas');
define('API_AUDITORIAS','http://ropas.spring.informaticapp.com:1644/api/ropas/auditoria');
define('API_TOKEN',      'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

function apiGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . API_TOKEN],
    ]);
    $json = curl_exec($ch);
    curl_close($ch);
    return json_decode($json, true) ?: [];
}

// Fetch y filtrar
$allE = apiGet(API_EMPRESAS);
$selfId   = $_SESSION['user']['empresa']['id'];
$empresas = array_filter($allE, fn($e) => $e['id'] !== $selfId);

$allA   = apiGet(API_AUDITORIAS);
$audits = array_filter($allA, fn($a) => $a['usuario']['id'] !== $_SESSION['user']['id']);
usort($audits, fn($a,$b)=> $b['id'] <=> $a['id']);
$audits = array_slice($audits, 0, 20);
?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SUPERadmin | Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    /* — Variables y temas — */
    :root { --primary:#0d6efd; --bg:#f8f9fa; --card-bg:#fff; --text:#212529; }
    [data-theme="OSCURO"]  { --primary:#212529; --bg:#343a40; --card-bg:#495057; --text:#f8f9fa; }
    [data-theme="AZUL"]    { --primary:#0dcaf0; --bg:#e7f5ff; --card-bg:#cff4fc; }
    [data-theme="ROJO"]    { --primary:#dc3545; --bg:#f8d7da; --card-bg:#f5c2c7; }
    [data-theme="VERDE"]   { --primary:#198754; --bg:#d1e7dd; --card-bg:#a3cfbb; }
    [data-theme="MORADO"]  { --primary:#6f42c1; --bg:#e2d8f9; --card-bg:#cabdf0; }
    [data-theme="NARANJA"] { --primary:#fd7e14; --bg:#fff4e6; --card-bg:#ffe5d0; }
    [data-theme="GRIS"]    { --primary:#6c757d; --bg:#e9ecef; --card-bg:#dee2e6; }

    body { background: var(--bg); color: var(--text); }
    /* — Header — */
    .navbar { background-color: var(--primary) !important; }
    .navbar-brand { font-weight: bold; color: #fff!important; }
    .nav-link { color: #fff!important; text-transform: uppercase; }
    /* — Botón tema — */
    .theme-btn { background:none; border:none; color:#fff; }
    .theme-dot { width:16px;height:16px;border:2px solid #fff;border-radius:50%;display:inline-block;margin-right:.5rem; }
    /* — Perfil — */
    .profile-btn, .profile-btn:focus { border:none; background:none!important; }
    .profile-btn img { width:40px;height:40px;border-radius:50%;border:2px solid #fff; }
    /* — Tarjetas empresas — */
    .emp-card { background: var(--card-bg); border:2px solid var(--primary); border-radius:1rem; transition:transform .2s,box-shadow .2s; }
    .emp-card:hover { transform: translateY(-4px); box-shadow:0 .5rem 1rem rgba(0,0,0,.1); }
    .emp-card h5 { color: var(--primary); }
    /* — Encabezado dinámico — */
    .welcome-title { color: var(--primary) !important; }
    /* — "Gota de agua" en auditorías — */
    .table-responsive {
      border: 2px solid var(--primary);
      border-radius: 2rem 0.5rem 2rem 0.5rem;
      overflow: hidden;
      box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
    }
    .audit-table {
      width:100%;
      margin:0;
      border-collapse: separate!important;
      border-spacing:0;
    }
    .audit-table thead th {
      background: var(--primary)!important;
      color: #fff!important;
    }
    .audit-table tbody td {
      border-top:1px solid var(--primary);
    }
    .audit-table tbody tr:hover {
      background: rgba(0,0,0,.03);
    }
    .search-input { max-width:400px; }
  </style>
</head>
<body>

  <!-- HEADER -->
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand">SUPERadmin</a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="#">EMPRESAS</a></li>
          <li class="nav-item"><a class="nav-link" href="#">AUDITORÍA</a></li>
        </ul>
        <!-- Tema -->
        <div class="dropdown me-3">
          <button class="theme-btn dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-palette-fill"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach(['CLÁSICO','OSCURO','AZUL','ROJO','VERDE','MORADO','NARANJA','GRIS'] as $t): ?>
            <li>
              <a href="#" class="dropdown-item theme-select" data-theme="<?= $t ?>">
                <span class="theme-dot" style="background:var(--<?= strtolower($t) ?>)"></span>
                <?= ucfirst(strtolower($t)) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <!-- Perfil -->
        <div class="dropdown">
          <button class="profile-btn dropdown-toggle" data-bs-toggle="dropdown">
            <img src="https://i.pravatar.cc/40?u=<?= $_SESSION['user']['id'] ?>" alt="">
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li class="px-3 py-2">
              <strong><?= htmlspecialchars($_SESSION['user']['nombre']) ?></strong><br>
              <small class="text-muted">(SUPERadmin)</small>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <!-- Título dinámico -->
    <h1 class="welcome-title">Bienvenido, SUPERadmin</h1>
    <p>Desde aquí podrás gestionar <strong>Empresas</strong> y <strong>Auditorías</strong>.</p>

    <!-- EMPRESAS -->
    <div class="d-flex justify-content-between align-items-center mt-5">
      <h2>Empresas Registradas</h2>
      <input id="searchEmp" class="form-control search-input" placeholder="Buscar empresa...">
    </div>
    <div id="empGrid" class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mt-2">
      <?php foreach($empresas as $e): ?>
      <div class="col emp-item">
        <?php if ($e['id'] == 5): ?>
          <!-- Empresa ID 5 redirecciona al dashboard -->
          <a href="dashboard.php" class="text-decoration-none">
            <div class="card emp-card h-100 p-3">
              <h5><?= htmlspecialchars($e['nombre']) ?></h5>
              <p class="small text-muted mb-0"><i class="bi bi-envelope"></i> <?= htmlspecialchars($e['correo']) ?></p>
              <small class="text-primary"><i class="bi bi-arrow-right"></i> Ir al Dashboard</small>
            </div>
          </a>
        <?php else: ?>
          <!-- Otras empresas redireccionan a empresa.php -->
          <a href="empresa.php?id=<?= $e['id'] ?>" class="text-decoration-none">
            <div class="card emp-card h-100 p-3">
              <h5><?= htmlspecialchars($e['nombre']) ?></h5>
              <p class="small text-muted mb-0"><i class="bi bi-envelope"></i> <?= htmlspecialchars($e['correo']) ?></p>
            </div>
          </a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- AUDITORÍAS -->
    <div class="d-flex justify-content-between align-items-center mt-5">
      <h2>Últimas Auditorías</h2>
      <input id="searchAud" class="form-control search-input" placeholder="Buscar auditoría...">
    </div>
    <div class="table-responsive mt-2">
      <table class="table audit-table">
        <thead>
          <tr>
            <th>ID</th><th>Usuario</th><th>Empresa</th><th>Sucursal</th><th>Evento</th><th>Fecha</th><th>Ver</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($audits as $a): ?>
          <tr class="audit-item">
            <td><?= $a['id'] ?></td>
            <td><?= htmlspecialchars($a['usuario']['nombre']) ?></td>
            <td><?= htmlspecialchars($a['usuario']['empresa']['nombre']) ?></td>
            <td><?= htmlspecialchars($a['sucursal']['nombre']) ?></td>
            <td><?= htmlspecialchars($a['evento']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($a['fecha'])) ?></td>
            <td>
              <button class="btn btn-outline-primary btn-sm btn-view"
                      data-audit='<?= json_encode($a) ?>'
                      data-bs-toggle="modal"
                      data-bs-target="#auditModal">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Detalle Auditoría -->
  <div class="modal fade" id="auditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalle Auditoría</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <pre id="auditDetails" style="white-space:pre-wrap;"></pre>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Tema persistente
    const root = document.documentElement;
    const saved = localStorage.getItem('theme') || 'CLÁSICO';
    root.setAttribute('data-theme', saved);
    document.querySelectorAll('.theme-select').forEach(el=>{
      el.addEventListener('click', e=>{
        e.preventDefault();
        const t = el.dataset.theme;
        root.setAttribute('data-theme', t);
        localStorage.setItem('theme', t);
      });
    });

    // Buscadores
    document.getElementById('searchEmp').addEventListener('input', e=>{
      const q=e.target.value.toLowerCase();
      document.querySelectorAll('.emp-item').forEach(el=>{
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
    document.getElementById('searchAud').addEventListener('input', e=>{
      const q=e.target.value.toLowerCase();
      document.querySelectorAll('.audit-item').forEach(el=>{
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });

    // Modal detalle JSON
    document.querySelectorAll('.btn-view').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const data = JSON.parse(btn.dataset.audit);
        document.getElementById('auditDetails').innerText = JSON.stringify(data, null, 2);
      });
    });
  </script>
</body>
</html>
