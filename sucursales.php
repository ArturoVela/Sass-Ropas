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

define('API_BASE','http://ropas.spring.informaticapp.com:1655/api/ropas');
define('API_TOKEN','eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

function apiRequest($url, $method = 'GET', $data = null) {
  $ch = curl_init();
  $options = [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Authorization: Bearer ' . API_TOKEN
    ],
  ];
  if ($data && in_array($method, ['POST', 'PUT'])) {
    $options[CURLOPT_POSTFIELDS] = json_encode($data);
  }
  curl_setopt_array($ch, $options);
  $response = curl_exec($ch);
  curl_close($ch);
  return json_decode($response, true) ?: [];
}

function registrarAuditoria($evento, $descripcion, $sucursalId = null) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] === 'SUPERadmin') return;

    $payload = [
        'usuario'     => ['id' => $_SESSION['user']['id']],
        'evento'      => $evento,
        'descripcion' => $descripcion,
    ];
    if ($sucursalId) {
        $payload['sucursal'] = ['id' => (int)$sucursalId];
    }

    $ch = curl_init(API_BASE . '/auditoria');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . API_TOKEN
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sucursalData = [
        'empresa' => ['id' => $empId],
        'nombre' => $_POST['nombre'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'correo' => $_POST['correo'] ?? '',
        'responsable' => $_POST['responsable'] ?? '',
        'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : 1,
    ];

    if ($action === 'create_sucursal' || $action === 'update_sucursal') {
        $method = ($action === 'create_sucursal') ? 'POST' : 'PUT';
        if ($action === 'update_sucursal') {
            $sucursalData['id'] = (int)$_POST['id'];
        }
        $result = apiRequest(API_BASE . '/sucursales', $method, $sucursalData);

        // Auditoría
        if ($action === 'create_sucursal') {
            $sucId = $result['id'] ?? null;
            registrarAuditoria('CREACIÓN DE SUCURSAL', "Se creó la sucursal '{$_POST['nombre']}'.", $sucId);
        } else {
            registrarAuditoria('MODIFICACIÓN DE SUCURSAL', "Se modificó la sucursal '{$_POST['nombre']}'.", $_POST['id']);
        }

    } elseif ($action === 'delete_sucursal') {
        apiRequest(API_BASE . '/sucursales/' . $_POST['id'], 'DELETE');
        // Auditoría - Nota: No podemos obtener el nombre aquí fácilmente, así que usamos el ID.
        registrarAuditoria('ELIMINACIÓN DE SUCURSAL', "Se eliminó la sucursal con ID {$_POST['id']}.", $_POST['id']);
    }
    
    header('Location: sucursales.php');
    exit;
}

$todasLasSucursales = apiRequest(API_BASE . '/sucursales');
$sucursales = array_filter($todasLasSucursales, fn($s) => isset($s['empresa']['id']) && $s['empresa']['id'] === $empId);
?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Sucursales | <?= $empName ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    :root { --primary: #0d6efd; --bg: #f8f9fa; --card-bg: #fff; --text: #212529; }
    [data-theme="OSCURO"]{--primary:#212529;--bg:#343a40;--card-bg:#495057;--text:#f8f9fa;}
    [data-theme="AZUL"]{--primary:#0dcaf0;--bg:#e7f5ff;--card-bg:#cff4fc;}[data-theme="ROJO"]{--primary:#dc3545;--bg:#f8d7da;--card-bg:#f5c2c7;}
    [data-theme="VERDE"]{--primary:#198754;--bg:#d1e7dd;--card-bg:#a3cfbb;}[data-theme="MORADO"]{--primary:#6f42c1;--bg:#e2d8f9;--card-bg:#cabdf0;}
    [data-theme="NARANJA"]{--primary:#fd7e14;--bg:#fff4e6;--card-bg:#ffe5d0;}[data-theme="GRIS"]{--primary:#6c757d;--bg:#e9ecef;--card-bg:#dee2e6;}
    body{background:var(--bg);color:var(--text);}.navbar{background-color:var(--primary) !important;}
    .navbar-brand,.nav-link{color:#fff !important;font-weight:600;text-transform:uppercase;}
    .theme-btn,.profile-btn{background:none;border:none;color:#fff;}
    .theme-dot{width:16px;height:16px;border:2px solid #fff;border-radius:50%;display:inline-block;margin-right:.5rem;}
    .profile-btn img{width:40px;height:40px;border-radius:50%;border:2px solid #fff;object-fit:cover;}
    .page-title{color:var(--primary);font-weight:700;}
    .card{background:var(--card-bg);border:2px solid var(--primary);border-radius:1rem;}
    .btn-primary-custom{background:var(--primary);border-color:var(--primary);color:#fff;}
    .suc-card{transition:transform .2s,box-shadow .2s;}.suc-card:hover{transform:translateY(-4px);box-shadow:0 .5rem 1rem rgba(0,0,0,.1);}
    .suc-card-title{color:var(--primary);}.search-input{max-width:400px;}.modal-header{background:var(--primary);color:#fff;}
    .modal-header .btn-close{filter:brightness(0) invert(1);}
  </style>
</head>
<body data-theme-base="<?= $isUserAdmin ? 'MORADO' : 'ROJO' ?>">
  <!-- HEADER -->
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php"><?= $empName ?></a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link active" href="sucursales.php">Sucursales</a></li>
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
            <button class="profile-btn dropdown-toggle" data-bs-toggle="dropdown"><img src="https://i.pravatar.cc/40?u=<?= $user['id'] ?>" alt="Avatar"></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li class="px-3 py-2"><strong><?= htmlspecialchars($user['nombre']) ?></strong><br><small class="text-muted">(<?= htmlspecialchars($user['rol']) ?>)</small></li>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title m-0"><i class="bi bi-shop-window me-2"></i>Sucursales de <?= $empName ?></h1>
            <p class="text-muted mb-0">Crea, visualiza y administra las sucursales de la empresa.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input id="searchSucursal" class="form-control search-input" placeholder="Buscar sucursal...">
            <button class="btn btn-primary-custom flex-shrink-0" data-bs-toggle="modal" data-bs-target="#createSucursalModal"><i class="bi bi-plus-circle me-1"></i>Crear</button>
        </div>
    </div>

    <!-- Listado -->
    <div id="sucursales-list" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
      <?php if (empty($sucursales)): ?>
          <div class="col-12"><p class="text-center text-muted mt-5">No hay sucursales registradas.</p></div>
      <?php else: ?>
          <?php foreach($sucursales as $s): ?>
            <div class="col sucursal-item" data-nombre="<?= strtolower(htmlspecialchars($s['nombre'])) ?>">
              <div class="card suc-card h-100 p-2 shadow-sm">
                <div class="card-body d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title suc-card-title mb-0"><?= htmlspecialchars($s['nombre']) ?></h5>
                    <span class="badge bg-<?= $s['estado'] == 1 ? 'success' : 'danger' ?> ms-2"><?= $s['estado'] == 1 ? 'Activa' : 'Inactiva' ?></span>
                  </div>
                  <p class="card-text text-muted small mt-2"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['direccion']) ?></p>
                  <div class="mt-auto d-flex justify-content-end gap-2 pt-2">
                      <a href="entrar_sucursal.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-primary-custom" title="Entrar"><i class="bi bi-box-arrow-in-right"></i></a>
                      <button class="btn btn-sm btn-outline-info" onclick='viewSucursal(<?= json_encode($s) ?>)' title="Ver"><i class="bi bi-eye"></i></button>
                      <button class="btn btn-sm btn-outline-warning" onclick='editSucursal(<?= json_encode($s) ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
                      <button class="btn btn-sm btn-outline-danger" onclick='deleteSucursal(<?= json_encode($s) ?>)' title="Eliminar"><i class="bi bi-trash"></i></button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal Crear/Editar -->
  <div class="modal fade" id="sucursalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title" id="sucursalModalTitle"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" id="sucursalAction">
            <input type="hidden" name="id" id="sucursalId">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" id="sucursalNombre" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Responsable</label><input name="responsable" id="sucursalResponsable" class="form-control" required></div>
              <div class="col-12"><label class="form-label">Dirección</label><input name="direccion" id="sucursalDireccion" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Teléfono</label><input name="telefono" id="sucursalTelefono" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Correo</label><input name="correo" type="email" id="sucursalCorreo" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Estado</label><select name="estado" id="sucursalEstado" class="form-select"><option value="1">Activa</option><option value="0">Inactiva</option></select></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary-custom" id="sucursalSubmitBtn"></button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Modal Ver -->
  <div class="modal fade" id="viewSucursalModal" tabindex="-1">
      <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detalles</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="viewSucursalBody"></div></div></div>
  </div>
  
  <!-- Modal Eliminar -->
  <div class="modal fade" id="deleteSucursalModal" tabindex="-1">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Confirmar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">¿Seguro que quieres eliminar la sucursal <strong id="deleteSucursalName"></strong>?</div>
              <div class="modal-footer">
                  <form method="post">
                      <input type="hidden" name="action" value="delete_sucursal">
                      <input type="hidden" name="id" id="deleteSucursalId">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                      <button type="submit" class="btn btn-danger">Sí, eliminar</button>
                  </form>
              </div>
          </div>
      </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // THEME HANDLER
      const root = document.documentElement, themeBase = document.body.dataset.themeBase||'CLÁSICO';
      const setTheme = t => { root.setAttribute('data-theme', t); localStorage.setItem('theme', t); };
      setTheme(localStorage.getItem('theme') || themeBase);
      document.querySelectorAll('.theme-select').forEach(el=>el.addEventListener('click', e => { e.preventDefault(); setTheme(el.dataset.theme); }));
      const themeColors={'CLÁSICO':'#0d6efd','OSCURO':'#212529','AZUL':'#0dcaf0','ROJO':'#dc3545','VERDE':'#198754','MORADO':'#6f42c1','NARANJA':'#fd7e14','GRIS':'#6c757d'};
      document.querySelectorAll('.theme-select .theme-dot').forEach(d=>{const t=d.parentElement.dataset.theme;d.style.background=themeColors[t];});
      
      // SEARCH
      document.getElementById('searchSucursal').addEventListener('input', e => {
          const q = e.target.value.toLowerCase();
          document.querySelectorAll('.sucursal-item').forEach(i => { i.style.display = i.dataset.nombre.includes(q) ? '' : 'none'; });
      });

      // MODALS
      const sucModal = new bootstrap.Modal(document.getElementById('sucursalModal'));
      const createBtn = document.querySelector('[data-bs-target="#createSucursalModal"]');
      if(createBtn) {
          createBtn.addEventListener('click', () => {
              document.getElementById('sucursalModalTitle').innerText = 'Crear Sucursal';
              document.getElementById('sucursalAction').value = 'create_sucursal';
              document.getElementById('sucursalSubmitBtn').innerHTML = '<i class="bi bi-plus-circle me-1"></i>Crear';
              document.querySelector('#sucursalModal form').reset();
              sucModal.show();
          });
      }
    });

    function editSucursal(s) {
        const modal = new bootstrap.Modal(document.getElementById('sucursalModal'));
        document.getElementById('sucursalModalTitle').innerText = 'Editar Sucursal';
        document.getElementById('sucursalAction').value = 'update_sucursal';
        document.getElementById('sucursalSubmitBtn').innerHTML = '<i class="bi bi-pencil-square me-1"></i>Actualizar';
        for(const key in s) {
            const el = document.getElementById('sucursal' + key.charAt(0).toUpperCase() + key.slice(1));
            if(el) el.value = s[key];
        }
        document.getElementById('sucursalId').value = s.id;
        modal.show();
    }
    
    function viewSucursal(s) {
        const body = document.getElementById('viewSucursalBody');
        body.innerHTML = `
            <p><strong>ID:</strong> ${s.id}</p>
            <p><strong>Nombre:</strong> ${s.nombre}</p>
            <p><strong>Dirección:</strong> ${s.direccion}</p>
            <p><strong>Teléfono:</strong> ${s.telefono}</p>
            <p><strong>Correo:</strong> ${s.correo}</p>
            <p><strong>Responsable:</strong> ${s.responsable}</p>
            <p><strong>Estado:</strong> ${s.estado == 1 ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-danger">Inactiva</span>'}</p>`;
        new bootstrap.Modal(document.getElementById('viewSucursalModal')).show();
    }
    
    function deleteSucursal(s) {
        document.getElementById('deleteSucursalName').innerText = s.nombre;
        document.getElementById('deleteSucursalId').value = s.id;
        new bootstrap.Modal(document.getElementById('deleteSucursalModal')).show();
    }
  </script>
</body>
</html>
