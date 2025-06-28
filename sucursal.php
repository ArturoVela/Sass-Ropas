<?php
session_start();
if (!isset($_SESSION['user'], $_SESSION['sucursal_id'])) {
    header('Location: sucursales.php');
    exit;
}
$user = $_SESSION['user'];
$empId = $user['empresa']['id'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);
$sucursalId = $_SESSION['sucursal_id'];
$isUserAdmin = ($user['rol'] === 'ADMIN');

define('API_BASE','http://ropas.spring.informaticapp.com:1644/api/ropas');
define('API_TOKEN','eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

function apiRequest($url, $method = 'GET', $data = null) {
  $ch = curl_init();
  $options = [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer '.API_TOKEN],
  ];
  if ($data) { $options[CURLOPT_POSTFIELDS] = json_encode($data); }
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
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . API_TOKEN],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// Procesar asignación de usuario existente a sucursal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_user') {
    $assignData = [
        'usuario_id' => (int)$_POST['usuario_id'],
        'sucursal_id' => (int)$sucursalId
    ];
    $response = apiRequest(API_BASE . '/usuario-sucursal', 'POST', $assignData);

    // Auditoría
    $usuarioNombre = $_POST['usuario_nombre'] ?? "ID {$_POST['usuario_id']}";
    $sucursalNombre = $sucursal['nombre'] ?? "ID {$sucursalId}";
    registrarAuditoria(
        'ASIGNACIÓN DE USUARIO A SUCURSAL', 
        "Se asignó el usuario '{$usuarioNombre}' a la sucursal '{$sucursalNombre}'.",
        $sucursalId
    );

    header("Location: sucursal.php");
    exit;
}

// Procesar edición de asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_assignment') {
    $assignmentId = $_POST['assignment_id'];
    $newSucursalId = $_POST['new_sucursal_id'];
    
    // Primero eliminar la asignación actual
    apiRequest(API_BASE . '/usuario-sucursal/' . $assignmentId, 'DELETE');
    
    // Crear nueva asignación
    $assignData = [
        'usuario_id' => (int)$_POST['usuario_id'],
        'sucursal_id' => (int)$newSucursalId
    ];
    apiRequest(API_BASE . '/usuario-sucursal', 'POST', $assignData);

    // Auditoría
    $usuarioNombre = $_POST['usuario_nombre'] ?? "ID {$_POST['usuario_id']}";
    $sucursalAnterior = $_POST['sucursal_anterior'] ?? "ID {$sucursalId}";
    $sucursalNueva = $_POST['sucursal_nueva'] ?? "ID {$newSucursalId}";
    registrarAuditoria(
        'EDICIÓN DE ASIGNACIÓN DE USUARIO', 
        "Se cambió la asignación del usuario '{$usuarioNombre}' de '{$sucursalAnterior}' a '{$sucursalNueva}'.",
        $sucursalId
    );

    header("Location: sucursal.php");
    exit;
}

// Procesar eliminación de asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_assignment') {
    $assignmentId = $_POST['assignment_id'];
    apiRequest(API_BASE . '/usuario-sucursal/' . $assignmentId, 'DELETE');

    // Auditoría
    $usuarioNombre = $_POST['usuario_nombre'] ?? "ID {$_POST['usuario_id']}";
    $sucursalNombre = $sucursal['nombre'] ?? "ID {$sucursalId}";
    registrarAuditoria(
        'ELIMINACIÓN DE ASIGNACIÓN DE USUARIO', 
        "Se eliminó la asignación del usuario '{$usuarioNombre}' de la sucursal '{$sucursalNombre}'.",
        $sucursalId
    );

    header("Location: sucursal.php");
    exit;
}

// Obtener datos
$sucursal = apiRequest(API_BASE . '/sucursales/' . $sucursalId);
$todosLosUsuarios = apiRequest(API_BASE . '/usuarios');
$asignacionesUsuarioSucursal = apiRequest(API_BASE . '/usuario-sucursal');
$sucursalesEmpresa = apiRequest(API_BASE . '/sucursales');

// Filtrar usuarios de la empresa actual
$usuariosEmpresa = array_filter($todosLosUsuarios, function($u) use ($empId) {
    return isset($u['empresa']['id']) && $u['empresa']['id'] === $empId;
});

// Obtener asignaciones de esta sucursal
$asignacionesSucursal = array_filter($asignacionesUsuarioSucursal, function($asignacion) use ($sucursalId) {
    return isset($asignacion['sucursal_id']['id']) && $asignacion['sucursal_id']['id'] === $sucursalId;
});

// Obtener IDs de usuarios ya asignados a esta sucursal
$usuariosAsignadosIds = array_map(function($asignacion) {
    return $asignacion['usuario_id']['id'];
}, $asignacionesSucursal);

// Usuarios de la empresa que NO están asignados a esta sucursal
$usuariosDisponibles = array_filter($usuariosEmpresa, function($u) use ($usuariosAsignadosIds) {
    return !in_array($u['id'], $usuariosAsignadosIds);
});

// Sucursales de la empresa (para edición)
$sucursalesEmpresa = array_filter($sucursalesEmpresa, function($s) use ($empId) {
    return isset($s['empresa']['id']) && $s['empresa']['id'] === $empId;
});
?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Detalle Sucursal | <?= htmlspecialchars($sucursal['nombre'] ?? 'N/A') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    :root { --primary: #0d6efd; --bg: #f8f9fa; --card-bg: #fff; --text: #212529; }
    [data-theme="OSCURO"]{--primary:#212529;--bg:#343a40;--card-bg:#495057;--text:#f8f9fa;}
    [data-theme="AZUL"]{--primary:#0dcaf0;--bg:#e7f5ff;--card-bg:#cff4fc;}
    [data-theme="ROJO"]{--primary:#dc3545;--bg:#f8d7da;--card-bg:#f5c2c7;}
    [data-theme="VERDE"]{--primary:#198754;--bg:#d1e7dd;--card-bg:#a3cfbb;}
    [data-theme="MORADO"]{--primary:#6f42c1;--bg:#e2d8f9;--card-bg:#cabdf0;}
    [data-theme="NARANJA"]{--primary:#fd7e14;--bg:#fff4e6;--card-bg:#ffe5d0;}
    [data-theme="GRIS"]{--primary:#6c757d;--bg:#e9ecef;--card-bg:#dee2e6;}
    body{background:var(--bg);color:var(--text);}.navbar{background-color:var(--primary) !important;}
    .navbar-brand,.nav-link{color:#fff !important;font-weight:600;text-transform:uppercase;}
    .theme-btn,.profile-btn{background:none;border:none;color:#fff;}
    .theme-dot{width:16px;height:16px;border:2px solid #fff;border-radius:50%;display:inline-block;margin-right:.5rem;}
    .profile-btn img{width:40px;height:40px;border-radius:50%;border:2px solid #fff;object-fit:cover;}
    .page-title{color:var(--primary);font-weight:700;}
    .card{background:var(--card-bg);border:2px solid var(--primary);border-radius:1rem;}
    .btn-primary-custom{background:var(--primary);border-color:var(--primary);color:#fff;}
    .table-responsive { border: 2px solid var(--primary); border-radius: 1rem; }
    .action-buttons .btn { margin: 0 2px; }
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
              <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title m-0">
            <i class="bi bi-shop-window me-2"></i>
            <?= htmlspecialchars($sucursal['nombre'] ?? 'Sucursal') ?>
        </h1>
        <a href="volver_sucursales.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver a Sucursales</a>
    </div>
    <p class="text-muted">Gestiona los usuarios de esta sucursal.</p>
    
    <div class="row g-4">
        <!-- Columna para asignar usuarios existentes -->
        <div class="col-lg-4">
            <div class="card p-3">
                <div class="card-body">
                    <h5 class="card-title page-title mb-3">Asignar Usuario Existente</h5>
                    <?php if(empty($usuariosDisponibles)): ?>
                        <p class="text-muted">No hay usuarios disponibles para asignar.</p>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="assign_user">
                            <div class="mb-3">
                                <label class="form-label">Seleccionar Usuario</label>
                                <select name="usuario_id" class="form-select" required>
                                    <option value="">Selecciona un usuario...</option>
                                    <?php foreach($usuariosDisponibles as $u): ?>
                                        <option value="<?= $u['id'] ?>" data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                                            <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['rol']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="usuario_nombre" id="usuario_nombre_assign">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success"><i class="bi bi-person-plus me-1"></i>Asignar a Sucursal</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Columna para listar asignaciones -->
        <div class="col-lg-8">
            <h3 class="text-secondary h4 mb-3">Usuarios Asignados a la Sucursal</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($asignacionesSucursal)): ?>
                            <tr><td colspan="6" class="text-center p-4 text-muted">No hay usuarios asignados a esta sucursal.</td></tr>
                        <?php else: ?>
                            <?php foreach($asignacionesSucursal as $asignacion): ?>
                            <tr>
                                <td><?= $asignacion['usuario_id']['id'] ?></td>
                                <td><?= htmlspecialchars($asignacion['usuario_id']['nombre']) ?></td>
                                <td><?= htmlspecialchars($asignacion['usuario_id']['correo']) ?></td>
                                <td><?= htmlspecialchars($asignacion['usuario_id']['rol']) ?></td>
                                <td><span class="badge bg-<?= $asignacion['usuario_id']['estado'] ? 'success' : 'danger' ?>"><?= $asignacion['usuario_id']['estado'] ? 'Activo' : 'Inactivo' ?></span></td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" 
                                            data-assignment-id="<?= $asignacion['id'] ?>"
                                            data-usuario-id="<?= $asignacion['usuario_id']['id'] ?>"
                                            data-usuario-nombre="<?= htmlspecialchars($asignacion['usuario_id']['nombre']) ?>"
                                            data-sucursal-actual="<?= htmlspecialchars($asignacion['sucursal_id']['nombre']) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta asignación?')">
                                        <input type="hidden" name="action" value="delete_assignment">
                                        <input type="hidden" name="assignment_id" value="<?= $asignacion['id'] ?>">
                                        <input type="hidden" name="usuario_id" value="<?= $asignacion['usuario_id']['id'] ?>">
                                        <input type="hidden" name="usuario_nombre" value="<?= htmlspecialchars($asignacion['usuario_id']['nombre']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>

  <!-- Modal para editar asignación -->
  <div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar Asignación de Usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit_assignment">
            <input type="hidden" name="assignment_id" id="edit_assignment_id">
            <input type="hidden" name="usuario_id" id="edit_usuario_id">
            <input type="hidden" name="usuario_nombre" id="edit_usuario_nombre">
            <input type="hidden" name="sucursal_anterior" value="<?= htmlspecialchars($sucursal['nombre']) ?>">
            
            <div class="mb-3">
              <label class="form-label">Usuario</label>
              <input type="text" class="form-control" id="edit_usuario_display" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Sucursal Actual</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($sucursal['nombre']) ?>" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Nueva Sucursal</label>
              <select name="new_sucursal_id" class="form-select" required>
                <option value="">Selecciona una sucursal...</option>
                <?php foreach($sucursalesEmpresa as $s): ?>
                  <?php if($s['id'] != $sucursalId): ?>
                    <option value="<?= $s['id'] ?>" data-nombre="<?= htmlspecialchars($s['nombre']) ?>">
                      <?= htmlspecialchars($s['nombre']) ?>
                    </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="sucursal_nueva" id="edit_sucursal_nueva">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const root = document.documentElement;
      const themeBase = document.body.dataset.themeBase || 'CLÁSICO';
      const storedTheme = localStorage.getItem('theme') || themeBase;
      root.setAttribute('data-theme', storedTheme);

      document.querySelectorAll('.theme-select').forEach(el => {
        el.addEventListener('click', e => {
          e.preventDefault();
          const theme = el.dataset.theme;
          root.setAttribute('data-theme', theme);
          localStorage.setItem('theme', theme);
        });
      });

      const themeColors = {'CLÁSICO':'#0d6efd','OSCURO':'#212529','AZUL':'#0dcaf0','ROJO':'#dc3545','VERDE':'#198754','MORADO':'#6f42c1','NARANJA':'#fd7e14','GRIS':'#6c757d'};
      document.querySelectorAll('.theme-select .theme-dot').forEach(dot => {
        const themeName = dot.parentElement.dataset.theme;
        dot.style.background = themeColors[themeName];
      });

      // Actualizar nombre de usuario en formulario de asignación
      const usuarioSelect = document.querySelector('select[name="usuario_id"]');
      if (usuarioSelect) {
        usuarioSelect.addEventListener('change', function() {
          const selectedOption = this.options[this.selectedIndex];
          const nombre = selectedOption.dataset.nombre || '';
          document.getElementById('usuario_nombre_assign').value = nombre;
        });
      }

      // Configurar modal de edición
      const editModal = document.getElementById('editModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const assignmentId = button.dataset.assignmentId;
          const usuarioId = button.dataset.usuarioId;
          const usuarioNombre = button.dataset.usuarioNombre;

          document.getElementById('edit_assignment_id').value = assignmentId;
          document.getElementById('edit_usuario_id').value = usuarioId;
          document.getElementById('edit_usuario_nombre').value = usuarioNombre;
          document.getElementById('edit_usuario_display').value = usuarioNombre;
        });
      }

      // Actualizar nombre de sucursal en formulario de edición
      const newSucursalSelect = document.querySelector('select[name="new_sucursal_id"]');
      if (newSucursalSelect) {
        newSucursalSelect.addEventListener('change', function() {
          const selectedOption = this.options[this.selectedIndex];
          const nombre = selectedOption.dataset.nombre || '';
          document.getElementById('edit_sucursal_nueva').value = nombre;
        });
      }
    });
  </script>
</body>
</html>
