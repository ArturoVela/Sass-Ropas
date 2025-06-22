<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}
$user = $_SESSION['user'];
$empId = $user['empresa']['id'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);
$isUserAdmin = ($user['rol'] === 'ADMIN');

// --- CONFIGURACIÓN API ---
define('API_BASE', 'http://ropas.spring.informaticapp.com:1655/api/ropas');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

function fetchApiData($endpoint): array {
  $ch = curl_init(API_BASE . $endpoint);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . API_TOKEN],
  ]);
  $raw = curl_exec($ch);
  curl_close($ch);
  return json_decode($raw, true) ?: [];
}

// Fetch Sucursales y Auditorías
$sucursales = array_filter(fetchApiData('/sucursales'), fn($s) => $s['empresa']['id'] === $empId);
$auditorias = array_filter(fetchApiData('/auditoria'), fn($a) => isset($a['usuario']['empresa']['id']) && $a['usuario']['empresa']['id'] === $empId);
usort($auditorias, fn($a, $b) => $b['id'] <=> $a['id']);

?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Dashboard | <?= $empName ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
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
    .navbar-brand, .nav-link { color: #fff !important; font-weight: 600; }
    .nav-link { text-transform: uppercase; }
    .theme-btn, .profile-btn { background: none; border: none; color: #fff; }
    .theme-dot { width: 16px; height: 16px; border: 2px solid #fff; border-radius: 50%; display: inline-block; margin-right: .5rem; }
    .profile-btn img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #fff; object-fit: cover; }
    .page-title { color: var(--primary); font-weight: 700; }
    .search-input { max-width: 350px; }

    .suc-card { background: var(--card-bg); border: 2px solid var(--primary); border-radius: 1rem; transition: transform .2s, box-shadow .2s; cursor: pointer; }
    .suc-card:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1); }
    .suc-card-title { color: var(--primary); }
    .suc-card .btn { cursor: pointer; }

    .table-responsive { border: 2px solid var(--primary); border-radius: 2rem 0.5rem; overflow: hidden; box-shadow: 0 .5rem 1rem rgba(0,0,0,.1); }
    .audit-table { border-collapse: separate; border-spacing: 0; }
    .audit-table thead th { background: var(--primary) !important; color: #fff !important; }
    .audit-table thead th:first-child { border-top-left-radius: 1.8rem; }
    .audit-table thead th:last-child { border-top-right-radius: 0.3rem; }
    .audit-table tbody tr:last-child td:first-child { border-bottom-left-radius: 1.8rem; }
    .audit-table tbody tr:last-child td:last-child { border-bottom-right-radius: 0.3rem; }
    .audit-table tbody tr:hover { background: rgba(0,0,0,.03); }

    #auditModal .modal-content {
      border-radius: 2rem 0.5rem 2rem 0.5rem;
      overflow: hidden;
      border: 2px solid var(--primary);
    }
    #auditModal .modal-header {
      background: var(--primary);
      color: #fff;
    }
  </style>
</head>
<body data-theme-base="MORADO">

  <!-- HEADER -->
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php"><?= $empName ?></a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="sucursales.php">Sucursales</a></li>
          <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li>
          <li class="nav-item"><a class="nav-link" href="auditoria.php">Auditoría</a></li>
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
            <button class="profile-btn dropdown-toggle" data-bs-toggle="dropdown"><img src="img/perfil.png" alt="Avatar"></button>
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

  <div class="container py-4">
    <!-- Alerta de suplantación -->
    <?php if (isset($_SESSION['superadmin_original_user'])): ?>
    <div class="alert alert-warning text-center">
      Estás viendo el panel como <strong><?= htmlspecialchars($user['nombre']) ?></strong>. 
      <a href="volver_superadmin.php" class="alert-link fw-bold">Volver a la vista de SUPERadmin</a>
    </div>
    <?php endif; ?>

    <!-- Sección Sucursales -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title m-0"><i class="bi bi-shop-window me-2"></i>Sucursales</h2>
        <input id="searchSucursal" class="form-control search-input" placeholder="Buscar sucursal por nombre...">
      </div>
      <div id="sucursales-list" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (empty($sucursales)): ?>
          <div class="col-12"><p class="text-center text-muted mt-4">No hay sucursales para mostrar.</p></div>
        <?php else: ?>
          <?php foreach($sucursales as $s): ?>
            <div class="col sucursal-item" 
                 data-nombre="<?= strtolower(htmlspecialchars($s['nombre'])) ?>"
                 onclick='openSucursalModal(<?= json_encode($s, JSON_HEX_QUOT) ?>)'>
              <div class="card suc-card h-100 p-2 shadow-sm">
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title suc-card-title"><?= htmlspecialchars($s['nombre']) ?></h5>
                  <p class="card-text text-muted small flex-grow-1">
                    <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($s['direccion']) ?><br>
                    <i class="bi bi-envelope-fill me-1"></i><?= htmlspecialchars($s['correo']) ?><br>
                    <i class="bi bi-person-check-fill me-1"></i><?= htmlspecialchars($s['responsable']) ?>
                  </p>
                  <a href="entrar_sucursal.php?id=<?= $s['id'] ?>" class="btn btn-sm mt-auto align-self-end" style="background: var(--primary); color: white;" onclick="event.stopPropagation()">
                    Entrar <i class="bi bi-arrow-right-circle"></i>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- Sección Auditoría -->
    <section>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title m-0"><i class="bi bi-journal-text me-2"></i>Registro de Auditoría</h2>
        <input id="searchAuditoria" class="form-control search-input" placeholder="Buscar en auditoría...">
      </div>
      <div class="table-responsive">
        <table id="audit-table" class="table audit-table table-hover w-100">
          <thead>
            <tr><th>ID</th><th>Evento</th><th>Usuario</th><th>Fecha y Hora</th><th>Ver</th></tr>
          </thead>
          <tbody>
            <?php if(empty($auditorias)): ?>
              <tr><td colspan="5" class="text-center p-4 text-muted">No hay registros de auditoría.</td></tr>
            <?php else: ?>
              <?php foreach($auditorias as $a): ?>
              <tr class="audit-item" data-searchable-text="<?= strtolower(htmlspecialchars(implode(' ', array_values($a)))) ?>">
                  <td><?= $a['id'] ?></td>
                  <td><?= htmlspecialchars($a['evento']) ?></td>
                  <td><?= htmlspecialchars($a['usuario']['nombre']) ?></td>
                  <td><?= date('d/m/Y H:i:s', strtotime($a['fecha'])) ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-info" onclick='openAuditModal(<?= json_encode($a, JSON_HEX_QUOT) ?>)'>
                      <i class="bi bi-eye"></i>
                    </button>
                  </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <!-- Modal Detalle Sucursal -->
  <div class="modal fade" id="sucursalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 1rem; border: 2px solid var(--primary);">
            <div class="modal-header" style="background: var(--primary); color: #fff;">
                <h5 class="modal-title" id="sucursalModalTitle"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Dirección</dt>
                    <dd class="col-sm-8" id="sucursal-direccion"></dd>
                    
                    <dt class="col-sm-4">Teléfono</dt>
                    <dd class="col-sm-8" id="sucursal-telefono"></dd>

                    <dt class="col-sm-4">Correo</dt>
                    <dd class="col-sm-8" id="sucursal-correo"></dd>

                    <dt class="col-sm-4">Responsable</dt>
                    <dd class="col-sm-8" id="sucursal-responsable"></dd>

                    <dt class="col-sm-4">Estado</dt>
                    <dd class="col-sm-8" id="sucursal-estado"></dd>
                </dl>
            </div>
        </div>
    </div>
  </div>

  <!-- Modal Detalle Auditoría -->
  <div class="modal fade" id="auditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="auditModalTitle"><i class="bi bi-info-circle me-2"></i>Detalle de Auditoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">ID Evento</dt>
                    <dd class="col-sm-8" id="audit-id"></dd>
                    <dt class="col-sm-4">Evento</dt>
                    <dd class="col-sm-8" id="audit-evento"></dd>
                    <dt class="col-sm-4">Fecha y Hora</dt>
                    <dd class="col-sm-8" id="audit-fecha"></dd>
                    <dt class="col-sm-4">Descripción</dt>
                    <dd class="col-sm-8" id="audit-descripcion"></dd>
                    <dt class="col-sm-4">Usuario</dt>
                    <dd class="col-sm-8" id="audit-usuario"></dd>
                    <div id="audit-sucursal-info" class="contents">
                      <dt class="col-sm-4">Sucursal</dt>
                      <dd class="col-sm-8" id="audit-sucursal"></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // --- MANEJO DEL TEMA ---
      const root = document.documentElement;
      const themeBase = document.body.dataset.themeBase || 'CLÁSICO';
      const themeColors = {'CLÁSICO':'#0d6efd','OSCURO':'#212529','AZUL':'#0dcaf0','ROJO':'#dc3545','VERDE':'#198754','MORADO':'#6f42c1','NARANJA':'#fd7e14','GRIS':'#6c757d'};

      const applyTheme = (theme) => {
        root.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        document.querySelectorAll('.theme-select .theme-dot').forEach(dot => {
          const t = dot.parentElement.dataset.theme;
          dot.style.background = themeColors[t];
        });
      };

      document.querySelectorAll('.theme-select').forEach(el => {
        el.addEventListener('click', e => {
          e.preventDefault();
          applyTheme(el.dataset.theme);
        });
      });

      applyTheme(localStorage.getItem('theme') || themeBase);
      
      // --- BUSCADOR DE SUCURSALES ---
      document.getElementById('searchSucursal').addEventListener('input', e => {
          const query = e.target.value.toLowerCase().trim();
          document.querySelectorAll('.sucursal-item').forEach(item => {
              const nombre = item.dataset.nombre;
              item.style.display = nombre.includes(query) ? '' : 'none';
          });
      });

      // --- BUSCADOR EN TABLA DE AUDITORÍA ---
      document.getElementById('searchAuditoria').addEventListener('input', e => {
          const query = e.target.value.toLowerCase().trim();
          document.querySelectorAll('#audit-table tbody tr').forEach(row => {
              if (row.querySelector('td').colSpan === 5) return; // No ocultar la fila "sin registros"
              const searchableText = row.textContent.toLowerCase();
              row.style.display = searchableText.includes(query) ? '' : 'none';
          });
      });

      // --- MODAL SUCURSAL ---
      const sucursalModal = new bootstrap.Modal(document.getElementById('sucursalModal'));
      window.openSucursalModal = (sucursalData) => {
        document.getElementById('sucursalModalTitle').textContent = sucursalData.nombre;
        document.getElementById('sucursal-direccion').textContent = sucursalData.direccion;
        document.getElementById('sucursal-telefono').textContent = sucursalData.telefono;
        document.getElementById('sucursal-correo').textContent = sucursalData.correo;
        document.getElementById('sucursal-responsable').textContent = sucursalData.responsable;
        
        const estadoBadge = sucursalData.estado == 1 
          ? '<span class="badge bg-success">Activa</span>' 
          : '<span class="badge bg-danger">Inactiva</span>';
        document.getElementById('sucursal-estado').innerHTML = estadoBadge;
        
        sucursalModal.show();
      };

      // --- MODAL AUDITORÍA ---
      const auditModal = new bootstrap.Modal(document.getElementById('auditModal'));
      window.openAuditModal = (auditData) => {
        document.getElementById('audit-id').textContent = auditData.id;
        document.getElementById('audit-evento').textContent = auditData.evento;
        document.getElementById('audit-fecha').textContent = new Date(auditData.fecha).toLocaleString('es-ES');
        document.getElementById('audit-descripcion').textContent = auditData.descripcion;
        document.getElementById('audit-usuario').textContent = auditData.usuario.nombre;

        const sucursalInfo = document.getElementById('audit-sucursal-info');
        if (auditData.sucursal && auditData.sucursal.nombre) {
            document.getElementById('audit-sucursal').textContent = auditData.sucursal.nombre;
            sucursalInfo.style.display = 'contents';
        } else {
            sucursalInfo.style.display = 'none';
        }
        
        auditModal.show();
      };
    });
  </script>
</body>
</html>