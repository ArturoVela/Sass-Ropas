<?php
session_start();
// 1. Verificación de ADMIN
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['ADMIN', 'SUPERadmin'])) {
    header('Location: index.php');
    exit;
}
$user = $_SESSION['user'];
$empresaId = $user['empresa']['id'];
$empresa = $user['empresa']; // El objeto empresa completo ya está en la sesión

// --- CONFIGURACIÓN Y FUNCIONES API ---
define('API_BASE', 'http://ropas.spring.informaticapp.com:1644/api/ropas');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII');

function apiRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . API_TOKEN],
    ];
    if ($data) { $options[CURLOPT_POSTFIELDS] = json_encode($data); }
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?: [];
}

function registrarAuditoria($evento, $descripcion) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] === 'SUPERadmin') return;
    $payload = json_encode([
        'usuario'     => ['id' => $_SESSION['user']['id']],
        'evento'      => $evento,
        'descripcion' => $descripcion,
    ]);
    $ch = curl_init(API_BASE . '/auditoria');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . API_TOKEN],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// --- OBTENER DATOS ---
$todosLosUsuarios = apiRequest(API_BASE . '/usuarios');
$usuariosEmpresa = array_filter($todosLosUsuarios, fn($u) => isset($u['empresa']['id']) && $u['empresa']['id'] === $empresaId);

// --- PROCESAMIENTO DE FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = isset($_POST['id']) ? (int)$_POST['id'] : null;

    if ($action === 'create_user' || $action === 'update_user') {
        $userData = [
            'empresa' => $empresa, // Usar el objeto completo de la empresa
            'nombre' => $_POST['nombre'],
            'correo' => $_POST['correo'],
            'rol' => $_POST['rol'],
            'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : 1,
        ];
        if ($userId) {
            $userData['id'] = $userId;
        }
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        } elseif ($action === 'update_user') {
            foreach ($usuariosEmpresa as $u) {
                if ($u['id'] == $userId) {
                    $userData['password'] = $u['password'];
                    break;
                }
            }
        }
        
        $method = ($action === 'create_user') ? 'POST' : 'PUT';
        apiRequest(API_BASE . '/usuarios', $method, $userData);

        // Auditoría
        if ($action === 'create_user') {
            registrarAuditoria('CREACIÓN DE USUARIO', "Se creó el usuario '{$_POST['nombre']}' con el rol '{$_POST['rol']}'.");
        } else {
            registrarAuditoria('MODIFICACIÓN DE USUARIO', "Se modificó el usuario '{$_POST['nombre']}' (ID: {$userId}).");
        }

    } elseif ($action === 'delete_user' && $userId) {
        apiRequest(API_BASE . '/usuarios/' . $userId, 'DELETE');
        // Auditoría
        registrarAuditoria('ELIMINACIÓN DE USUARIO', "Se eliminó el usuario con ID {$userId}.");
    }
    
    header('Location: usuarios.php');
    exit;
}

$empName = htmlspecialchars($empresa['nombre'] ?? 'Empresa', ENT_QUOTES);
$isUserAdmin = ($user['rol'] === 'ADMIN');
?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Usuarios | <?= $empName ?></title>
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
    .user-card{transition:transform .2s,box-shadow .2s;}.user-card:hover{transform:translateY(-4px);box-shadow:0 .5rem 1rem rgba(0,0,0,.1);}
    .user-card-title{color:var(--primary);}.search-input{max-width:400px;}.modal-header{background:var(--primary);color:#fff;}
    .modal-header .btn-close{filter:brightness(0) invert(1);}
  </style>
</head>
<body data-theme-base="<?= $isUserAdmin ? 'MORADO' : 'ROJO' ?>">
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php"><?= $empName ?></a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="sucursales.php">Sucursales</a></li>
          <li class="nav-item"><a class="nav-link active" href="usuarios.php">Usuarios</a></li>
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
        <h1 class="page-title m-0"><i class="bi bi-people me-2"></i>Usuarios de <?= $empName ?></h1>
        <div>
            <input id="search" class="form-control d-inline-block search-input" placeholder="Buscar usuario...">
            <button class="btn btn-primary-custom" onclick="openUserModal()"><i class="bi bi-plus-circle me-1"></i>Crear</button>
        </div>
    </div>

    <div id="user-list" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
      <?php if (empty($usuariosEmpresa)): ?>
          <div class="col-12"><p class="text-center text-muted mt-5">No hay usuarios registrados.</p></div>
      <?php else: ?>
          <?php foreach($usuariosEmpresa as $u): ?>
            <div class="col user-item" data-nombre="<?= strtolower(htmlspecialchars($u['nombre'])) ?>" data-correo="<?= strtolower(htmlspecialchars($u['correo'])) ?>">
              <div class="card user-card h-100 p-2 shadow-sm">
                <div class="card-body">
                  <h5 class="card-title user-card-title"><?= htmlspecialchars($u['nombre']) ?></h5>
                  <p class="card-text text-muted mb-1"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($u['correo']) ?></p>
                  <p class="card-text text-muted mb-2"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($u['rol']) ?></p>
                  <span class="badge bg-<?= $u['estado'] ? 'success' : 'danger' ?>"><?= $u['estado'] ? 'Activo' : 'Inactivo' ?></span>
                  <?php if(isset($u['sucursal'])): ?>
                    <span class="badge bg-info text-dark"><i class="bi bi-shop-window me-1"></i><?= htmlspecialchars($u['sucursal']['nombre']) ?></span>
                  <?php endif; ?>
                  <div class="position-absolute top-0 end-0 p-2">
                      <button class="btn btn-sm btn-outline-warning" onclick='openUserModal(<?= json_encode($u) ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
                      <button class="btn btn-sm btn-outline-danger" onclick='deleteUser(<?= json_encode($u) ?>)' title="Eliminar"><i class="bi bi-trash"></i></button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal Crear/Editar Usuario -->
  <div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title" id="userModalTitle"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" id="userAction">
            <input type="hidden" name="id" id="userId">
            <div class="mb-3"><label>Nombre</label><input name="nombre" id="userNombre" class="form-control" required></div>
            <div class="mb-3"><label>Correo</label><input type="email" name="correo" id="userCorreo" class="form-control" required></div>
            <div class="mb-3"><label>Contraseña</label><input type="password" name="password" class="form-control"><small class="form-text text-muted">Dejar en blanco para no cambiar</small></div>
            <div class="mb-3"><label>Rol</label><select name="rol" id="userRol" class="form-select"><option value="ADMIN">Admin</option><option value="CAJA">Caja</option><option value="VENDEDOR">Vendedor</option></select></div>
            <div class="mb-3"><label>Estado</label><select name="estado" id="userEstado" class="form-select"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary-custom" id="userSubmitBtn"></button>
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

      document.getElementById('search').addEventListener('input', e => {
          const q = e.target.value.toLowerCase();
          document.querySelectorAll('.user-item').forEach(i => {
              const name = i.dataset.nombre || '';
              const email = i.dataset.correo || '';
              i.style.display = name.includes(q) || email.includes(q) ? '' : 'none';
          });
      });
    });

    const userModal = new bootstrap.Modal(document.getElementById('userModal'));
    
    function openUserModal(user = null) {
        const form = document.querySelector('#userModal form');
        form.reset();
        if (user) {
            document.getElementById('userModalTitle').innerText = 'Editar Usuario';
            document.getElementById('userAction').value = 'update_user';
            document.getElementById('userSubmitBtn').innerHTML = '<i class="bi bi-pencil-square me-1"></i>Actualizar';
            document.getElementById('userId').value = user.id;
            document.getElementById('userNombre').value = user.nombre;
            document.getElementById('userCorreo').value = user.correo;
            document.getElementById('userRol').value = user.rol;
            document.getElementById('userEstado').value = user.estado;
            document.querySelector('#userModal input[name="password"]').required = false;
        } else {
            document.getElementById('userModalTitle').innerText = 'Crear Usuario';
            document.getElementById('userAction').value = 'create_user';
            document.getElementById('userSubmitBtn').innerHTML = '<i class="bi bi-plus-circle me-1"></i>Crear';
            document.querySelector('#userModal input[name="password"]').required = true;
        }
        userModal.show();
    }

    function deleteUser(user) {
        if (confirm(`¿Seguro que quieres eliminar al usuario ${user.nombre}?`)) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `<input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="${user.id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
  </script>
</body>
</html> 