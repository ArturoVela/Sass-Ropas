<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'SUPERadmin') {
  header('Location: index.php');
  exit;
}

// --- API de Empresas y Auditorías ---
define('API_EMPRESAS',   'http://ropas.spring.informaticapp.com:1655/api/ropas/empresas');
define('API_AUDITORIAS', 'http://ropas.spring.informaticapp.com:1655/api/ropas/auditoria');
define('API_TOKEN',      'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

function apiGet(string $url): array
{
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
$allE      = apiGet(API_EMPRESAS);
$selfId    = $_SESSION['user']['empresa']['id'];
$empresas  = array_filter($allE, fn($e) => $e['id'] !== $selfId);

$allA      = apiGet(API_AUDITORIAS);
$audits    = array_filter($allA, fn($a) => $a['usuario']['id'] !== $_SESSION['user']['id']);
usort($audits, fn($a, $b) => $b['id'] <=> $a['id']);
$audits    = array_slice($audits, 0, 20);
?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>SUPERadmin | Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
        rel="stylesheet"/>
  <style>
    /* — Variables y temas — */
    :root {
      --primary: #0d6efd;
      --bg: #f8f9fa;
      --card-bg: #fff;
      --text: #212529;
    }
    [data-theme="OSCURO"]  { --primary: #212529; --bg: #343a40; --card-bg: #495057; --text: #f8f9fa; }
    [data-theme="AZUL"]    { --primary: #0dcaf0; --bg: #e7f5ff; --card-bg: #cff4fc; }
    [data-theme="ROJO"]    { --primary: #dc3545; --bg: #f8d7da; --card-bg: #f5c2c7; }
    [data-theme="VERDE"]   { --primary: #198754; --bg: #d1e7dd; --card-bg: #a3cfbb; }
    [data-theme="MORADO"]  { --primary: #6f42c1; --bg: #e2d8f9; --card-bg: #cabdf0; }
    [data-theme="NARANJA"] { --primary: #fd7e14; --bg: #fff4e6; --card-bg: #ffe5d0; }
    [data-theme="GRIS"]    { --primary: #6c757d; --bg: #e9ecef; --card-bg: #dee2e6; }
    [data-theme="CLÁSICO"] { --primary: #0d6efd; --bg: #f8f9fa; --card-bg: #fff; --text: #212529; --clásico: #0d6efd; }

    body {
      background: var(--bg);
      color: var(--text);
    }

    /* — Header — */
    .navbar {
      background-color: var(--primary) !important;
    }
    .navbar-brand,
    .nav-link {
      color: #fff !important;
      font-weight: 600;
    }
    .nav-link {
      text-transform: uppercase;
    }

    /* — Theme & Profile buttons — */
    .theme-btn,
    .profile-btn {
      background: none;
      border: none;
      color: #fff;
    }
    .theme-dot {
      width: 16px; height: 16px;
      border: 2px solid #fff;
      border-radius: 50%;
      display: inline-block;
      margin-right: .5rem;
    }
    .profile-btn img {
      width: 40px; height: 40px;
      border-radius: 50%;
      border: 2px solid #fff;
      object-fit: cover;
    }

    /* — Bienvenida — */
    .welcome-title {
      color: var(--primary);
      font-weight: 700;
    }

    /* — Tarjetas empresa — */
    .emp-card {
      background: var(--card-bg);
      border: 2px solid var(--primary);
      border-radius: 1rem;
      transition: transform .2s, box-shadow .2s;
    }
    .emp-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1);
    }
    .emp-card h5 {
      color: var(--primary);
    }

    /* — Modal Detalle Empresa (gota de agua) — */
    #companyModal .modal-content {
      border-radius: 2rem 0.5rem 2rem 0.5rem;
      overflow: hidden;
      box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
    }
    #companyModal .modal-header {
      background: var(--primary);
      color: #fff;
    }
    #companyModal .modal-body {
      background: var(--card-bg);
    }

    /* — Auditorías tabla (gota de agua) — */
    .table-responsive {
      border: 2px solid var(--primary);
      border-radius: 2rem 0.5rem 2rem 0.5rem;
      overflow: hidden;
      box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
    }
    .audit-table thead th {
      background: var(--primary) !important;
      color: #fff !important;
    }
    .audit-table tbody tr:hover {
      background: rgba(0, 0, 0, .03);
    }
    .table-water-drop {
      border-collapse: separate;
      border-spacing: 0;
    }
    .table-water-drop thead th:first-child {
      border-top-left-radius: 1.8rem;
    }
    .table-water-drop thead th:last-child {
      border-top-right-radius: 0.3rem;
    }
    .table-water-drop tbody tr:last-child td:first-child {
      border-bottom-left-radius: 1.8rem;
    }
    .table-water-drop tbody tr:last-child td:last-child {
      border-bottom-right-radius: 0.3rem;
    }
    .search-input {
      max-width: 400px;
    }

    /* — Zoom-scroll horizontal solo tabla — */
    body.zoom-scroll .table-responsive {
      overflow-x: auto;
      overflow-y: hidden;
      min-width: 100%;
    }

    /* — Modal Auditoría (gota de agua + dl ordenado) — */
    #auditModal .modal-content {
      border-radius: 2rem 0.5rem 2rem 0.5rem;
      overflow: hidden;
      box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
    }
    #auditModal .modal-header {
      background: var(--primary);
      color: #fff;
    }
    #auditModal .modal-body {
      background: var(--card-bg);
    }
    #auditModal dt {
      font-weight: 600;
      margin-top: .5rem;
    }
    #auditModal dd {
      margin-bottom: .5rem;
    }
    #auditModal hr {
      border-top: 1px solid rgba(0,0,0,.1);
    }
    .modal-body-compact dl {
      margin-bottom: 0;
    }
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
          <li class="nav-item"><a class="nav-link" href="empresas.php">EMPRESAS</a></li>
          <li class="nav-item"><a class="nav-link" href="auditoria.php">AUDITORÍA</a></li>
        </ul>
        <!-- Theme selector -->
        <div class="dropdown me-3">
          <button class="theme-btn dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-palette-fill"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach (['CLÁSICO','OSCURO','AZUL','ROJO','VERDE','MORADO','NARANJA','GRIS'] as $t): ?>
            <li>
              <a class="dropdown-item theme-select" href="#" data-theme="<?= $t ?>">
                <span class="theme-dot" style="background:var(--<?= strtolower($t) ?>)"></span>
                <?= ucfirst(strtolower($t)) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <!-- Profile -->
        <div class="dropdown">
          <button class="profile-btn dropdown-toggle" data-bs-toggle="dropdown">
            <img src="https://i.pravatar.cc/40?u=<?= $_SESSION['user']['id'] ?>" alt="Avatar">
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li class="px-3 py-2">
              <strong><?= htmlspecialchars($_SESSION['user']['nombre']) ?></strong><br>
              <small class="text-muted">(SUPERadmin)</small>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="logout.php">
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <!-- Bienvenida -->
    <h1 class="welcome-title">Bienvenido, SUPERadmin</h1>
    <p>Desde aquí podrás gestionar <strong>Empresas</strong> y <strong>Auditorías</strong>.</p>

    <!-- EMPRESAS -->
    <div class="d-flex justify-content-between align-items-center mt-5">
      <h2>Empresas Registradas</h2>
      <input id="searchEmp" class="form-control search-input" placeholder="Buscar empresa...">
    </div>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mt-2">
      <?php foreach ($empresas as $e): ?>
      <div class="col emp-item">
        <div class="card emp-card h-100 p-3 btn-show-company"
             data-company='<?= json_encode($e, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'
             style="cursor:pointer">
          <h5><?= htmlspecialchars($e['nombre']) ?></h5>
          <p class="small text-muted mb-0">
            <i class="bi bi-envelope me-1"></i>
            <?= htmlspecialchars($e['correo']) ?>
          </p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- AUDITORÍAS -->
    <div class="d-flex justify-content-between align-items-center mt-5">
      <h2>Últimas Auditorías</h2>
      <input id="searchAud" class="form-control search-input" placeholder="Buscar auditoría...">
    </div>
    <div class="table-responsive mt-2">
      <table class="table audit-table table-hover align-middle w-100 table-water-drop">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Empresa</th>
            <th>Sucursal</th>
            <th>Evento</th>
            <th>Fecha</th>
            <th>Ver</th>
          </tr>
        </thead>
        <tbody id="audits-table-body">
          <?php foreach ($audits as $a): ?>
          <tr class="audit-item" data-search-text="<?= strtolower(htmlspecialchars(json_encode($a))) ?>">
            <td><?= $a['id'] ?></td>
            <td><?= htmlspecialchars($a['usuario']['nombre']) ?></td>
            <td><?= htmlspecialchars($a['usuario']['empresa']['nombre']) ?></td>
            <td><?= isset($a['sucursal']) ? htmlspecialchars($a['sucursal']['nombre']) : '' ?></td>
            <td><?= htmlspecialchars($a['evento']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($a['fecha'])) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-info" onclick='openAuditModal(<?= json_encode($a) ?>)'>
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Detalle Empresa -->
  <div class="modal fade" id="companyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalle de Empresa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <ul class="list-unstyled mb-0">
            <li><strong>ID:</strong> <span id="c-id"></span></li>
            <li><strong>Nombre:</strong> <span id="c-name"></span></li>
            <li><strong>RUC:</strong> <span id="c-ruc"></span></li>
            <li><strong>Dirección:</strong> <span id="c-dir"></span></li>
            <li><strong>Teléfono:</strong> <span id="c-tel"></span></li>
            <li><strong>Correo:</strong> <span id="c-mail"></span></li>
            <li><strong>Estado:</strong> <span id="c-est"></span></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Auditoría -->
  <div class="modal fade" id="auditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Detalle de Auditoría</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body modal-body-compact">
          <dl class="row">
            <dt class="col-sm-3">ID Evento:</dt>
            <dd class="col-sm-9" id="audit-id"></dd>
            
            <dt class="col-sm-3">Fecha y Hora:</dt>
            <dd class="col-sm-9" id="audit-fecha"></dd>

            <dt class="col-sm-3">Evento:</dt>
            <dd class="col-sm-9" id="audit-evento"></dd>

            <dt class="col-sm-3">Descripción:</dt>
            <dd class="col-sm-9" id="audit-descripcion"></dd>
          </dl>
          <hr>
          <h6 class="text-primary">Usuario</h6>
          <dl class="row">
            <dt class="col-sm-3">Nombre:</dt>
            <dd class="col-sm-9" id="audit-user-nombre"></dd>
            <dt class="col-sm-3">Correo:</dt>
            <dd class="col-sm-9" id="audit-user-correo"></dd>
            <dt class="col-sm-3">Empresa:</dt>
            <dd class="col-sm-9" id="audit-user-empresa"></dd>
          </dl>
          <div id="audit-sucursal-details">
            <hr>
            <h6 class="text-primary">Sucursal</h6>
            <dl class="row">
              <dt class="col-sm-3">Nombre:</dt>
              <dd class="col-sm-9" id="audit-sucursal-nombre"></dd>
              <dt class="col-sm-3">Dirección:</dt>
              <dd class="col-sm-9" id="audit-sucursal-direccion"></dd>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Tema persistente
    const root = document.documentElement;
    root.setAttribute('data-theme', localStorage.getItem('theme') || 'CLÁSICO');
    document.querySelectorAll('.theme-select').forEach(el => {
      el.addEventListener('click', e => {
        e.preventDefault();
        const t = el.dataset.theme;
        root.setAttribute('data-theme', t);
        localStorage.setItem('theme', t);
      });
    });

    // Zoom-scroll horizontal
    function detectZoom() {
      const z = window.devicePixelRatio || window.outerWidth / window.innerWidth;
      document.body.classList.toggle('zoom-scroll', z >= 2);
    }
    window.addEventListener('load', detectZoom);
    window.addEventListener('resize', detectZoom);
    setInterval(detectZoom, 1000);

    // Buscadores
    document.getElementById('searchEmp').addEventListener('input', e => {
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('.emp-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
    document.getElementById('searchAud').addEventListener('input', e => {
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('.audit-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });

    // Modal Empresa
    document.querySelectorAll('.btn-show-company').forEach(card => {
      card.addEventListener('click', () => {
        const c = JSON.parse(card.dataset.company);
        document.getElementById('c-id').innerText   = c.id;
        document.getElementById('c-name').innerText = c.nombre;
        document.getElementById('c-ruc').innerText  = c.ruc;
        document.getElementById('c-dir').innerText  = c.direccion;
        document.getElementById('c-tel').innerText  = c.telefono;
        document.getElementById('c-mail').innerText = c.correo;
        document.getElementById('c-est').innerText  = c.estado ? 'Activo' : 'Inactivo';
        new bootstrap.Modal(document.getElementById('companyModal')).show();
      });
    });

    // Modal Auditoría
    const auditModal = new bootstrap.Modal(document.getElementById('auditModal'));
    
    function openAuditModal(auditData) {
      document.getElementById('audit-id').textContent = auditData.id;
      document.getElementById('audit-fecha').textContent = new Date(auditData.fecha).toLocaleString('es-ES');
      document.getElementById('audit-evento').textContent = auditData.evento;
      document.getElementById('audit-descripcion').textContent = auditData.descripcion;

      document.getElementById('audit-user-nombre').textContent = auditData.usuario.nombre;
      document.getElementById('audit-user-correo').textContent = auditData.usuario.correo;
      document.getElementById('audit-user-empresa').textContent = auditData.usuario.empresa.nombre;

      const sucursalDetails = document.getElementById('audit-sucursal-details');
      if (auditData.sucursal) {
        document.getElementById('audit-sucursal-nombre').textContent = auditData.sucursal.nombre;
        document.getElementById('audit-sucursal-direccion').textContent = auditData.sucursal.direccion;
        sucursalDetails.style.display = 'block';
      } else {
        sucursalDetails.style.display = 'none';
      }
      
      auditModal.show();
    }
  </script>
</body>
</html>
