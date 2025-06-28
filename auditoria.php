<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['SUPERadmin', 'ADMIN'])) {
  header('Location: index.php');
  exit;
}
$user = $_SESSION['user'];
$isSuperAdmin = $user['rol'] === 'SUPERadmin';
$empresaId = $user['empresa']['id'] ?? null;
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);

// --- CONFIGURACIÓN API ---
define('API_BASE', 'http://ropas.spring.informaticapp.com:1644/api/ropas');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

function apiRequest($url) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . API_TOKEN],
  ]);
  $response = curl_exec($ch);
  curl_close($ch);
  return json_decode($response, true) ?: [];
}

// Obtener datos según el rol del usuario
$isSuperAdmin = $_SESSION['user']['rol'] === 'SUPERadmin';
$empresaId = $_SESSION['user']['empresa']['id'] ?? null;

// Obtener auditorías
$auditorias = apiRequest(API_BASE . '/auditoria');

// Filtrar auditorías según el rol
if ($isSuperAdmin) {
  // SUPERadmin ve todas las auditorías
  $auditoriasFiltradas = $auditorias;
  $empresas = apiRequest(API_BASE . '/empresas');
  $usuarios = apiRequest(API_BASE . '/usuarios');
  
  // Crear mapas para filtros
  $empresasMap = array_column($empresas, 'nombre', 'id');
  $usuariosMap = array_column($usuarios, 'nombre', 'id');
  $roles = array_unique(array_column($usuarios, 'rol'));
} else {
  // ADMIN solo ve auditorías de su empresa
  $auditoriasFiltradas = array_filter($auditorias, fn($a) => $a['usuario']['empresa']['id'] === $empresaId);
  
  // Para ADMIN, solo mostrar usuarios y roles de su empresa
  $usuarios = apiRequest(API_BASE . '/usuarios');
  $usuariosEmpresa = array_filter($usuarios, fn($u) => $u['empresa']['id'] === $empresaId);
  
  $empresasMap = []; // ADMIN no necesita filtro de empresa
  $usuariosMap = array_column($usuariosEmpresa, 'nombre', 'id');
  $roles = array_unique(array_column($usuariosEmpresa, 'rol'));
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Auditoría | <?= $isSuperAdmin ? 'SUPERadmin' : $empName ?></title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root { --primary: #6f42c1; --bg: #f8f9fa; --card-bg: #fff; --text: #212529; }
    [data-theme="OSCURO"]  { --primary: #212529; --bg: #343a40; --card-bg: #495057; --text: #f8f9fa; }
    [data-theme="AZUL"]    { --primary: #0dcaf0; --bg: #e7f5ff; --card-bg: #cff4fc; }
    [data-theme="ROJO"]    { --primary: #dc3545; --bg: #f8d7da; --card-bg: #f5c2c7; }
    [data-theme="VERDE"]   { --primary: #198754; --bg: #d1e7dd; --card-bg: #a3cfbb; }
    [data-theme="MORADO"]  { --primary: #6f42c1; --bg: #e2d8f9; --card-bg: #cabdf0; }
    [data-theme="NARANJA"] { --primary: #fd7e14; --bg: #fff4e6; --card-bg: #ffe5d0; }
    [data-theme="GRIS"]    { --primary: #6c757d; --bg: #e9ecef; --card-bg: #dee2e6; }
    [data-theme="CLÁSICO"] { --primary: #0d6efd; --bg: #f8f9fa; --card-bg: #fff; --text: #212529; }
    
    body { background: var(--bg); color: var(--text); font-family: 'Segoe UI',-apple-system,BlinkMacSystemFont,Roboto,'Helvetica Neue',sans-serif; }
    .navbar { background-color: var(--primary) !important; }
    .navbar-brand, .nav-link { color: #fff !important; font-weight: 600; text-transform: uppercase; }
    .theme-btn, .profile-btn { background: none; border: none; color: #fff; }
    .theme-dot { width: 16px; height: 16px; border: 2px solid #fff; border-radius: 50%; display: inline-block; margin-right: .5rem; }
    .profile-btn img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #fff; object-fit: cover; }
    .page-title { color: var(--primary); font-weight: 700; }
    
    .table-responsive {
      border: 2px solid var(--primary);
      border-radius: 2rem 0.5rem;
      overflow: hidden;
      box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
    }
    .audit-table { border-collapse: separate; border-spacing: 0; }
    .audit-table thead th { background: var(--primary) !important; color: #fff !important; }
    .audit-table thead th:first-child { border-top-left-radius: 1.8rem; }
    .audit-table thead th:last-child { border-top-right-radius: 0.3rem; }
    .audit-table tbody tr:last-child td:first-child { border-bottom-left-radius: 1.8rem; }
    .audit-table tbody tr:last-child td:last-child { border-bottom-right-radius: 0.3rem; }
    .audit-table tbody tr:hover { background: rgba(0,0,0,.05); }

    #auditModal .modal-content { border-radius: 1rem; border: 2px solid var(--primary); }
    #auditModal .modal-header { background: var(--primary); color: #fff; }
  </style>
</head>
<body data-theme-base="<?= $isSuperAdmin ? 'CLÁSICO' : 'MORADO' ?>">

  <!-- HEADER -->
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand" href="<?= $isSuperAdmin ? 'superadmin_dashboard.php' : 'dashboard.php' ?>">
        <?= $isSuperAdmin ? 'SUPERadmin' : $empName ?>
      </a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <?php if ($isSuperAdmin): ?>
            <li class="nav-item"><a class="nav-link" href="empresas.php">Empresas</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="sucursales.php">Sucursales</a></li>
            <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link active" href="auditoria.php">Auditoría</a></li>
        </ul>
        <div class="d-flex align-items-center">
          <div class="dropdown me-3">
            <button class="theme-btn dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-palette-fill"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php foreach (['CLÁSICO','OSCURO','AZUL','ROJO','VERDE','MORADO','NARANJA','GRIS'] as $t): ?>
              <li><a class="dropdown-item theme-select" href="#" data-theme="<?= $t ?>"><span class="theme-dot"></span> <?= ucfirst(strtolower($t)) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="dropdown">
            <button class="profile-btn dropdown-toggle" data-bs-toggle="dropdown">
              <img src="img/perfil.png" alt="Avatar">
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li class="px-3 py-2">
                <strong><?= htmlspecialchars($user['nombre']) ?></strong><br>
                <small class="text-muted">(<?= htmlspecialchars($user['rol']) ?>)</small>
              </li>
              <li><hr class="dropdown-divider"></li>
              <?php if (isset($_SESSION['superadmin_original_user'])): ?>
                <li><a class="dropdown-item" href="volver_superadmin.php"><i class="bi bi-arrow-left me-1"></i>Volver a SUPERadmin</a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <div class="container-fluid p-4">
    <h1 class="page-title">Panel de Auditoría</h1>
    <p>
      <?php if ($isSuperAdmin): ?>
      Visualiza y filtra los registros de actividad del sistema.
      <?php else: ?>
      Visualiza y filtra los registros de actividad de <?= htmlspecialchars($_SESSION['user']['empresa']['nombre']) ?>.
      <?php endif; ?>
    </p>

    <!-- Controles y Filtros -->
    <div class="card filters-card mb-4" style="background: var(--card-bg); border-radius: 1rem; padding: 1.5rem;">
      <div class="row g-3 align-items-end">
        <!-- Filtros -->
        <?php if ($isSuperAdmin): ?>
        <div class="col-lg-3 col-md-6">
          <label for="filterEmpresa" class="form-label fw-bold">Empresa</label>
          <select id="filterEmpresa" class="form-select">
            <option value="">Todas</option>
            <?php foreach ($empresasMap as $id => $nombre): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-lg-2 col-md-6">
          <label for="filterRol" class="form-label fw-bold">Rol</label>
          <select id="filterRol" class="form-select">
            <option value="">Todos</option>
            <?php foreach ($roles as $rol): ?>
            <option value="<?= $rol ?>"><?= htmlspecialchars($rol) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-3 col-md-6">
          <label for="filterUsuario" class="form-label fw-bold">Usuario</label>
          <select id="filterUsuario" class="form-select">
            <option value="">Todos</option>
            <?php foreach ($usuariosMap as $id => $nombre): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <!-- Búsqueda y Exportación -->
        <div class="col-lg-4 col-md-6 ms-auto text-end">
          <div class="input-group">
            <input id="searchAud" class="form-control" placeholder="Buscar en tabla...">
            <button id="exportExcel" class="btn btn-success" title="Exportar a Excel"><i class="bi bi-file-earmark-excel"></i></button>
            <button id="exportPdf" class="btn btn-danger" title="Exportar a PDF"><i class="bi bi-file-earmark-pdf"></i></button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tabla de Auditorías -->
    <h2 class="h4 text-secondary"><i class="bi bi-clipboard-check me-2"></i>Registros de Auditoría</h2>
    <div class="table-responsive mt-3">
      <table class="table audit-table table-hover w-100 mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <?php if ($isSuperAdmin): ?>
            <th>Empresa</th>
            <?php endif; ?>
            <th>Sucursal</th>
            <th>Evento</th>
            <th>Fecha</th>
            <th>Ver</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($auditoriasFiltradas as $a): ?>
          <tr class="audit-item" 
              <?php if ($isSuperAdmin): ?>
              data-empresa="<?= $a['usuario']['empresa']['id'] ?? '' ?>"
              <?php endif; ?>
              data-rol="<?= $a['usuario']['rol'] ?? '' ?>"
              data-usuario="<?= $a['usuario']['id'] ?? '' ?>">
            <td><?= $a['id'] ?></td>
            <td><?= htmlspecialchars($a['usuario']['nombre'] ?? 'N/A') ?></td>
            <?php if ($isSuperAdmin): ?>
            <td><?= htmlspecialchars($a['usuario']['empresa']['nombre'] ?? '—') ?></td>
            <?php endif; ?>
            <td><?= htmlspecialchars($a['sucursal']['nombre'] ?? '—') ?></td>
            <td><?= htmlspecialchars($a['evento']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($a['fecha'])) ?></td>
            <td>
              <button class="btn btn-outline-primary btn-sm btn-view"
                      data-audit='<?= json_encode($a, JSON_HEX_QUOT|JSON_HEX_APOS) ?>'>
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalle de Auditoría</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">ID Auditoría</dt>
            <dd class="col-sm-8" id="a-id"></dd>
            <dt class="col-sm-4">Evento</dt>
            <dd class="col-sm-8" id="a-event"></dd>
            <dt class="col-sm-4">Descripción</dt>
            <dd class="col-sm-8" id="a-desc"></dd>
            <dt class="col-sm-4">Fecha</dt>
            <dd class="col-sm-8" id="a-date"></dd>
            <dt class="col-sm-4">Estado</dt>
            <dd class="col-sm-8" id="a-state"></dd>
            <hr class="my-3 w-100">
            <dt class="col-sm-4">ID Usuario</dt>
            <dd class="col-sm-8" id="u-id"></dd>
            <dt class="col-sm-4">Nombre Usuario</dt>
            <dd class="col-sm-8" id="u-name"></dd>
            <dt class="col-sm-4">Correo Usuario</dt>
            <dd class="col-sm-8" id="u-email"></dd>
            <dt class="col-sm-4">Rol Usuario</dt>
            <dd class="col-sm-8" id="u-role"></dd>
            <dt class="col-sm-4">Estado Usuario</dt>
            <dd class="col-sm-8" id="u-state"></dd>
            <hr class="my-3 w-100">
            <dt class="col-sm-4">ID Sucursal</dt>
            <dd class="col-sm-8" id="s-id"></dd>
            <dt class="col-sm-4">Nombre Sucursal</dt>
            <dd class="col-sm-8" id="s-name"></dd>
            <dt class="col-sm-4">Dirección Sucursal</dt>
            <dd class="col-sm-8" id="s-address"></dd>
            <dt class="col-sm-4">Teléfono Sucursal</dt>
            <dd class="col-sm-8" id="s-phone"></dd>
            <dt class="col-sm-4">Correo Sucursal</dt>
            <dd class="col-sm-8" id="s-email"></dd>
            <dt class="col-sm-4">Responsable</dt>
            <dd class="col-sm-8" id="s-resp"></dd>
            <dt class="col-sm-4">Estado Sucursal</dt>
            <dd class="col-sm-8" id="s-state"></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
    setInterval(detectZoom, 500);

    // Filtros
    function applyFilters() {
      const empresa = document.getElementById('filterEmpresa')?.value || '';
      const rol = document.getElementById('filterRol').value;
      const usuario = document.getElementById('filterUsuario').value;
      
      document.querySelectorAll('.audit-item').forEach(row => {
        const showEmpresa = !empresa || row.dataset.empresa === empresa;
        const showRol = !rol || row.dataset.rol === rol;
        const showUsuario = !usuario || row.dataset.usuario === usuario;
        
        row.style.display = (showEmpresa && showRol && showUsuario) ? '' : 'none';
      });
    }

    <?php if ($isSuperAdmin): ?>
    document.getElementById('filterEmpresa').addEventListener('change', applyFilters);
    <?php endif; ?>
    document.getElementById('filterRol').addEventListener('change', applyFilters);
    document.getElementById('filterUsuario').addEventListener('change', applyFilters);

    // Búsqueda
    document.getElementById('searchAud').addEventListener('input', e => {
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('.audit-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });

    // Modal Auditoría
    document.querySelectorAll('.btn-view').forEach(btn => {
      btn.addEventListener('click', () => {
        const a = JSON.parse(btn.dataset.audit);
        document.getElementById('a-id').innerText      = a.id;
        document.getElementById('a-event').innerText   = a.evento;
        document.getElementById('a-desc').innerText    = a.descripcion;
        document.getElementById('a-date').innerText    = new Date(a.fecha).toLocaleString();
        document.getElementById('a-state').innerText   = a.estado ? 'Activo' : 'Inactivo';

        document.getElementById('u-id').innerText      = a.usuario.id;
        document.getElementById('u-name').innerText    = a.usuario.nombre;
        document.getElementById('u-email').innerText   = a.usuario.correo;
        document.getElementById('u-role').innerText    = a.usuario.rol;
        document.getElementById('u-state').innerText   = a.usuario.estado ? 'Activo' : 'Inactivo';

        document.getElementById('s-id').innerText      = a.sucursal.id;
        document.getElementById('s-name').innerText    = a.sucursal.nombre;
        document.getElementById('s-address').innerText = a.sucursal.direccion;
        document.getElementById('s-phone').innerText   = a.sucursal.telefono;
        document.getElementById('s-email').innerText   = a.sucursal.correo;
        document.getElementById('s-resp').innerText    = a.sucursal.responsable;
        document.getElementById('s-state').innerText   = a.sucursal.estado ? 'Activo' : 'Inactivo';

        new bootstrap.Modal(document.getElementById('auditModal')).show();
      });
    });

    // Exportar a Excel
    document.getElementById('exportExcel').addEventListener('click', () => {
      const visibleRows = Array.from(document.querySelectorAll('.audit-item')).filter(row => 
        row.style.display !== 'none'
      );
      
      const data = visibleRows.map(row => {
        const cells = row.querySelectorAll('td');
        const dataObj = {
          'ID': cells[0].textContent,
          'Usuario': cells[1].textContent,
          'Sucursal': cells[<?= $isSuperAdmin ? '3' : '2' ?>].textContent,
          'Evento': cells[<?= $isSuperAdmin ? '4' : '3' ?>].textContent,
          'Fecha': cells[<?= $isSuperAdmin ? '5' : '4' ?>].textContent
        };
        <?php if ($isSuperAdmin): ?>
        dataObj['Empresa'] = cells[2].textContent;
        <?php endif; ?>
        return dataObj;
      });

      const ws = XLSX.utils.json_to_sheet(data);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Auditorías');
      XLSX.writeFile(wb, 'auditorias.xlsx');
    });

    // Exportar a PDF
    document.getElementById('exportPdf').addEventListener('click', () => {
      const visibleRows = Array.from(document.querySelectorAll('.audit-item')).filter(row => 
        row.style.display !== 'none'
      );
      
      const data = visibleRows.map(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [
          cells[0].textContent,
          cells[1].textContent,
          cells[<?= $isSuperAdmin ? '3' : '2' ?>].textContent,
          cells[<?= $isSuperAdmin ? '4' : '3' ?>].textContent,
          cells[<?= $isSuperAdmin ? '5' : '4' ?>].textContent
        ];
        <?php if ($isSuperAdmin): ?>
        rowData.splice(2, 0, cells[2].textContent);
        <?php endif; ?>
        return rowData;
      });

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      
      const headers = ['ID', 'Usuario', <?= $isSuperAdmin ? "'Empresa', " : '' ?>'Sucursal', 'Evento', 'Fecha'];
      
      doc.autoTable({
        head: [headers],
        body: data,
        startY: 20,
        styles: { fontSize: 8 },
        headStyles: { fillColor: [13, 110, 253] }
      });
      
      doc.save('auditorias.pdf');
    });
  </script>
</body>
</html> 