<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'SUPERadmin') {
    header('Location: index.php');
    exit;
}
if (!isset($_GET['id'])) {
    header('Location: empresas.php');
    exit;
}
$empresaId = (int)$_GET['id'];
$superAdminId = $_SESSION['user']['id'];

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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['status' => $httpCode, 'data' => json_decode($response, true)];
}

function registrarAuditoria($evento, $descripcion) {
    global $superAdminId;
    if (!isset($superAdminId)) return;
    apiRequest(API_BASE . '/auditoria', 'POST', [
        'usuario' => ['id' => $superAdminId], 'evento' => $evento, 'descripcion' => $descripcion
    ]);
}

// Recoger mensajes de la sesión (de redirecciones)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message'], $_SESSION['messageType']);
}

$empresa = apiRequest(API_BASE . '/empresas/' . $empresaId)['data'];
$empName = htmlspecialchars($empresa['nombre'] ?? 'Empresa', ENT_QUOTES);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $usuarioId = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $empNameForAudit = apiRequest(API_BASE . '/empresas/' . $empresaId)['data']['nombre'] ?? 'N/A';

    if ($action === 'create' || $action === 'update') {
        $userData = [
            'empresa' => ['id' => $empresaId],
            'nombre'   => $_POST['nombre'],
            'correo'   => $_POST['correo'],
            'rol'      => $_POST['rol'],
            'estado'   => (int)$_POST['estado'],
        ];
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }

        if ($action === 'create') {
            $result = apiRequest(API_BASE . '/usuarios', 'POST', $userData);
            if ($result['status'] === 201 || $result['status'] === 200) {
                $_SESSION['message'] = "Usuario '{$_POST['nombre']}' creado con éxito.";
                $_SESSION['messageType'] = 'success';
                registrarAuditoria('CREACIÓN DE USUARIO', "Se creó el usuario '{$_POST['nombre']}' en la empresa '{$empNameForAudit}'.");
            } else {
                $error = $result['data']['message'] ?? 'Error desconocido.';
                $_SESSION['message'] = "Error al crear: " . htmlspecialchars($error);
                $_SESSION['messageType'] = 'danger';
            }
        } else { // update
            $userData['id'] = $usuarioId;
            $result = apiRequest(API_BASE . '/usuarios/' . $usuarioId, 'PUT', $userData);
            if ($result['status'] === 200) {
                $_SESSION['message'] = "Usuario '{$_POST['nombre']}' actualizado con éxito.";
                $_SESSION['messageType'] = 'success';
                registrarAuditoria('EDICIÓN DE USUARIO', "Se actualizó al usuario '{$_POST['nombre']}' (ID:{$usuarioId}) en '{$empNameForAudit}'.");
            } else {
                $error = $result['data']['message'] ?? 'Error desconocido.';
                $_SESSION['message'] = "Error al actualizar: " . htmlspecialchars($error);
                $_SESSION['messageType'] = 'danger';
            }
        }

    } elseif ($action === 'delete' && $usuarioId) {
        $userToDelete = apiRequest(API_BASE . '/usuarios/' . $usuarioId)['data'];
        $nombreBorrado = $userToDelete['nombre'] ?? 'ID ' . $usuarioId;
        
        $result = apiRequest(API_BASE . '/usuarios/' . $usuarioId, 'DELETE');
        if($result['status'] === 204 || $result['status'] === 200) {
            $_SESSION['message'] = "Usuario '{$nombreBorrado}' eliminado con éxito.";
            $_SESSION['messageType'] = 'success';
            registrarAuditoria('ELIMINACIÓN DE USUARIO', "Se eliminó al usuario '{$nombreBorrado}' de la empresa '{$empNameForAudit}'.");
        } else {
            $error = $result['data']['message'] ?? 'Error desconocido.';
            $_SESSION['message'] = "Error al eliminar: " . htmlspecialchars($error);
            $_SESSION['messageType'] = 'danger';
        }
    }
    
    header('Location: empresa_usuarios.php?id=' . $empresaId);
    exit;
}

$todosLosUsuarios = apiRequest(API_BASE . '/usuarios')['data'] ?? [];
$usuariosEmpresa = array_filter($todosLosUsuarios, fn($u) => isset($u['empresa']['id']) && $u['empresa']['id'] === $empresaId);

?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Usuarios de <?= $empName ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    :root { --primary:#0d6efd; --primary-rgb:13,110,253; --bg:#f8f9fa; --card-bg:#fff; --text:#212529; --clásico:#0d6efd; }
    [data-theme="OSCURO"]{--primary:#212529;--primary-rgb:33,37,41;--bg:#343a40;--card-bg:#495057;--text:#f8f9fa;}
    [data-theme="AZUL"]{--primary:#0dcaf0;--primary-rgb:13,202,240;--bg:#e7f5ff;--card-bg:#cff4fc;}
    [data-theme="ROJO"]{--primary:#dc3545;--primary-rgb:220,53,69;--bg:#f8d7da;--card-bg:#f5c2c7;}
    [data-theme="VERDE"]{--primary:#198754;--primary-rgb:25,135,84;--bg:#d1e7dd;--card-bg:#a3cfbb;}
    [data-theme="MORADO"]{--primary:#6f42c1;--primary-rgb:111,66,193;--bg:#e2d8f9;--card-bg:#cabdf0;}
    [data-theme="NARANJA"]{--primary:#fd7e14;--primary-rgb:253,126,20;--bg:#fff4e6;--card-bg:#ffe5d0;}
    [data-theme="GRIS"]{--primary:#6c757d;--primary-rgb:108,117,125;--bg:#e9ecef;--card-bg:#dee2e6;}
    body{background:var(--bg);color:var(--text);}
    .navbar{background-color:var(--primary) !important;}
    .navbar-brand,.nav-link{color:#fff !important;font-weight:600;text-transform:uppercase;}
    .page-title{color:var(--primary);font-weight:700;}
    .card{background:var(--card-bg);border:2px solid var(--primary);border-radius:1rem;}
    .user-card{transition:transform .2s,box-shadow .2s;}.user-card:hover{transform:translateY(-4px);box-shadow:0 .5rem 1rem rgba(0,0,0,.1);}
    .user-card-title{color:var(--primary);}.search-input{max-width:400px;}
    .modal-header{background:var(--primary);color:#fff;}.modal-header .btn-close{filter:brightness(0) invert(1);}
    .btn-primary-custom{background:var(--primary);border-color:var(--primary);color:#fff;}
    .theme-btn, .profile-btn { background: none; border: none; color: #fff; }
    .theme-dot {width:16px;height:16px;border:2px solid #fff;border-radius:50%;display:inline-block;margin-right:.5rem;}
    .profile-btn img{width:40px;height:40px;border-radius:50%;border:2px solid #fff;object-fit:cover;}
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand" href="superadmin_dashboard.php">SUPERadmin</a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="empresas.php">EMPRESAS</a></li>
          <li class="nav-item"><a class="nav-link" href="auditoria.php">AUDITORÍA</a></li>
        </ul>
        <div class="d-flex align-items-center">
            <div class="dropdown me-3">
              <button class="theme-btn dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-palette-fill"></i></button>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach (['CLÁSICO','OSCURO','AZUL','ROJO','VERDE','MORADO','NARANJA','GRIS'] as $t): ?>
                <li><a class="dropdown-item theme-select" href="#" data-theme="<?= $t ?>"><span class="theme-dot" style="background:var(--<?= strtolower($t) ?>)"></span> <?= ucfirst(strtolower($t)) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="dropdown">
              <button class="profile-btn dropdown-toggle" data-bs-toggle="dropdown"><img src="https://i.pravatar.cc/40?u=<?= $superAdminId ?>" alt="Avatar"></button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2"><strong><?= htmlspecialchars($_SESSION['user']['nombre']) ?></strong><br><small class="text-muted">(SUPERadmin)</small></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</a></li>
              </ul>
            </div>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <?php if (isset($message)): ?>
      <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title m-0"><i class="bi bi-people me-2"></i>Usuarios de <?= $empName ?></h1>
        <div>
            <input id="search" class="form-control d-inline-block search-input" placeholder="Buscar usuario...">
            <button class="btn btn-primary-custom" onclick="openUserModal()"><i class="bi bi-plus-circle me-1"></i>Crear</button>
            <a href="empresas.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
        </div>
    </div>

    <div id="user-list" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
      <?php if (empty($usuariosEmpresa)): ?>
          <div class="col-12"><p class="text-center text-muted mt-5">No hay usuarios registrados para esta empresa.</p></div>
      <?php else: ?>
          <?php foreach($usuariosEmpresa as $u): ?>
            <div class="col user-item" data-nombre="<?= strtolower(htmlspecialchars($u['nombre'])) ?>" data-correo="<?= strtolower(htmlspecialchars($u['correo'])) ?>">
              <div class="card user-card h-100 p-2 shadow-sm">
                <div class="card-body">
                  <h5 class="card-title user-card-title"><?= htmlspecialchars($u['nombre']) ?></h5>
                  <p class="card-text text-muted mb-1"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($u['correo']) ?></p>
                  <p class="card-text text-muted mb-2"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($u['rol']) ?></p>
                  <span class="badge bg-<?= $u['estado'] ? 'success' : 'danger' ?>"><?= $u['estado'] ? 'Activo' : 'Inactivo' ?></span>
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

  <div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header"><h5 class="modal-title" id="userModalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" name="action" id="userAction">
            <input type="hidden" name="id" id="userId">
            <div class="mb-3"><label class="form-label">Nombre</label><input name="nombre" id="userNombre" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Correo</label><input type="email" name="correo" id="userCorreo" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Contraseña</label><input type="password" name="password" id="userPassword" class="form-control"><small class="form-text text-muted">Dejar en blanco para no cambiar</small></div>
            <div class="mb-3"><label class="form-label">Rol</label><select name="rol" id="userRol" class="form-select"><option value="ADMIN">Admin</option><option value="CAJA">Caja</option><option value="VENDEDOR">Vendedor</option></select></div>
            <div class="mb-3"><label class="form-label">Estado</label><select name="estado" id="userEstado" class="form-select"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary-custom" id="userSubmitBtn"></button></div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
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

    document.getElementById('search').addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('.user-item').forEach(i => {
            i.style.display = (i.dataset.nombre.includes(q) || i.dataset.correo.includes(q)) ? '' : 'none';
        });
    });

    const userModal = new bootstrap.Modal(document.getElementById('userModal'));
    function openUserModal(user = null) {
        const form = document.querySelector('#userModal form');
        form.reset();
        if (user) {
            document.getElementById('userModalTitle').innerText = 'Editar Usuario';
            document.getElementById('userAction').value = 'update';
            document.getElementById('userSubmitBtn').innerHTML = '<i class="bi bi-pencil-square me-1"></i>Actualizar';
            document.getElementById('userId').value = user.id;
            document.getElementById('userNombre').value = user.nombre;
            document.getElementById('userCorreo').value = user.correo;
            document.getElementById('userRol').value = user.rol;
            document.getElementById('userEstado').value = user.estado;
            document.getElementById('userPassword').required = false;
        } else {
            document.getElementById('userModalTitle').innerText = 'Crear Usuario';
            document.getElementById('userAction').value = 'create';
            document.getElementById('userSubmitBtn').innerHTML = '<i class="bi bi-plus-circle me-1"></i>Crear';
            document.getElementById('userId').value = '';
            document.getElementById('userPassword').required = true;
        }
        userModal.show();
    }

    function deleteUser(user) {
        if (confirm(`¿Seguro que quieres eliminar al usuario ${user.nombre}?`)) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${user.id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
  </script>
</body>
</html> 