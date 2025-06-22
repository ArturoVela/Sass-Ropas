<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

$user    = $_SESSION['user'];
$empId   = $user['empresa']['id'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);

define('API_BASE','http://ropas.spring.informaticapp.com:1688/api/ropas');
define('API_TOKEN','eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmODNmOTk0ZDhjYjlkNWQ1YmVmYzM2YTM5ZWNkYTNiNzliYmI3Y2EyYjNlODQyODA0NTA3N2IyZjllOTUwODA5IiwiaWF0IjoxNzUwMjIxNDc2LCJleHAiOjQ5MDM4MjE0NzZ9.jCScz9PRkyb7W0_NeU66aLcCt2NxyatATJz7Pblo0SM');

function fetchSucursales(): array {
    $ch = curl_init(API_BASE . '/sucursales');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.API_TOKEN],
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    return json_decode($raw, true) ?: [];
}

function fetchUsuarios(): array {
    try {
        $ch = curl_init('http://ropas.spring.informaticapp.com:1688/api/ropas/usuarios');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'],
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $raw !== false) {
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }
        
        return [];
    } catch (Exception $e) {
        return [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si es creación de sucursal o usuario
    if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
        // Crear nuevo usuario
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/usuarios',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "empresa": '.$empId.',
                "nombre": "'.$_POST['nombre'].'",
                "correo": "'.$_POST['correo'].'",
                "password": "'.$_POST['password'].'",
                "rol": "'.$_POST['rol'].'",
                "estado": 1
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
            ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        // --- Registrar en Auditoría ---
        $fecha_actual = date('Y-m-d\TH:i:s');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/auditoria',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "usuario": {"id":'.$user['id'].'},
                "sucursal": {"id":1},
                "evento": "CREACIÓN DE USUARIO",
                "descripcion": "Se creó nuevo usuario: '.$_POST['nombre'].' - Rol: '.$_POST['rol'].'",
                "fecha": "'.$fecha_actual.'",
                "estado": 1
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
            ),
        ));
        curl_exec($curl);
        curl_close($curl);
        
        header('Location: sucursales.php?success=user_created');
        exit;
    } else {
        // Crear nueva sucursal (lógica existente)
        $data = [
            'empresa'     => ['id' => $empId],
            'nombre'      => $_POST['nombre'],
            'direccion'   => $_POST['direccion'],
            'telefono'    => $_POST['telefono'],
            'correo'      => $_POST['correo'],
            'responsable' => $_POST['responsable'],
            'estado'      => 1
        ];
        $ch = curl_init(API_BASE . '/sucursales');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer '.API_TOKEN,
                'Content-Type: application/json'
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);
        
        // --- Registrar en Auditoría ---
        $fecha_actual = date('Y-m-d\TH:i:s');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/auditoria',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "usuario": {"id":'.$user['id'].'},
                "sucursal": {"id":1},
                "evento": "CREACIÓN DE SUCURSAL",
                "descripcion": "Se creó nueva sucursal: '.$_POST['nombre'].' - Responsable: '.$_POST['responsable'].' - Dirección: '.$_POST['direccion'].'",
                "fecha": "'.$fecha_actual.'",
                "estado": 1
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
            ),
        ));
        curl_exec($curl);
        curl_close($curl);
        
        header('Location: sucursales.php');
        exit;
    }
}

$sucs = array_filter(fetchSucursales(), fn($s)=> $s['empresa']['id']===$empId);
$allUserSucursales = fetchUsuarios();
$usersInEmpresa = array_filter($allUserSucursales, function($user) use ($empId) { 
    return isset($user['empresa']) && $user['empresa'] == $empId; 
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sucursales | <?= $empName ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="css/dashboard.css" rel="stylesheet"/>
  <style>
    :root {
      --header-height: 70px;
      --sidebar-width: 250px;
      --border-color: #dee2e6;
      --shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
    .main-header { position: fixed; top: 0; left: var(--sidebar-width); right: 0; height: var(--header-height); background-color: #fff; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; padding: 0 2rem; z-index: 1030; transition: all 0.3s ease-in-out; }
    .header-content { display: flex; justify-content: space-between; align-items: center; width: 100%; }
    .page-title { font-size: 1.5rem; font-weight: 600; color: #343a40; margin: 0; }
    .user-menu { position: relative; }
    .user-menu-toggle { background: transparent; border: none; display: flex; align-items: center; cursor: pointer; padding: 0.5rem; border-radius: 50px; transition: background-color 0.2s; }
    .user-menu-toggle:hover { background-color: #f1f1f1; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #007bff; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 10px; }
    .user-info { text-align: left; margin-right: 10px; }
    .user-name { font-weight: 600; font-size: 0.9rem; }
    .user-role { font-size: 0.75rem; color: #6c757d; }
    .user-menu-toggle i { transition: transform 0.2s; }
    .user-menu-dropdown { display: none; position: absolute; top: 100%; right: 0; background: #fff; border-radius: 0.5rem; box-shadow: var(--shadow); width: 250px; z-index: 1040; margin-top: 10px; border: 1px solid var(--border-color); }
    .user-menu-dropdown.show { display: block; }
    .dropdown-header { padding: 1rem; border-bottom: 1px solid var(--border-color); }
    .dropdown-item { display: flex; align-items: center; padding: 0.75rem 1rem; color: #343a40; font-weight: 500; text-decoration: none; }
    .dropdown-item i { margin-right: 1rem; width: 20px; text-align: center; color: #6c757d; }
    .dropdown-item:hover { background-color: #f8f9fa; }
    .dropdown-divider { height: 1px; background: var(--border-color); margin: 0.5rem 0; }
    .main-content { margin-top: var(--header-height); padding: 2rem; min-height: calc(100vh - var(--header-height)); transition: all 0.3s ease-in-out; }
    
   @media (min-width: 768px) {

/* Fija el offcanvas al lado izquierdo */
#sidebarOffcanvas.offcanvas-start {
  position: fixed !important;
  top: 0;
  left: 0;
  bottom: 0;
  width: 250px;
  transform: none !important;
  visibility: visible !important;
  border-right: 1px solid rgba(255, 255, 255, 0.2);
  z-index: 1020;
}

/* Hace que el body del sidebar ocupe toda la altura */
#sidebarOffcanvas .offcanvas-body {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

/* Mueve el contenido principal */
.content {
  margin-left: 250px;
}
}
  </style>
</head>

<body class="bg-light">

  <?php include 'dashboard_sidebar.php'; ?>

  <div class="d-flex">
    <main class="content flex-grow-1 p-4">
      <h1 class="display-6 text-danger">Gestión de Sucursales</h1>

      <!-- Formulario para crear sucursal -->
      <div class="card mb-5 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Crear nueva sucursal</h5>
          <form method="post" class="row g-3">
            <div class="col-md-6"><input name="nombre" class="form-control" placeholder="Nombre" required></div>
            <div class="col-md-6"><input name="responsable" class="form-control" placeholder="Responsable" required></div>
            <div class="col-md-6"><input name="direccion" class="form-control" placeholder="Dirección"></div>
            <div class="col-md-6"><input name="telefono" class="form-control" placeholder="Teléfono"></div>
            <div class="col-md-6"><input name="correo" type="email" class="form-control" placeholder="Correo"></div>
            <div class="col-12"><button class="btn btn-danger">Crear Sucursal</button></div>
          </form>
        </div>
      </div>

      <!-- Listado de sucursales -->
      <h2 class="h4 text-danger-emphasis mb-3">Crear usuarios para sucursales activas</h2>
      <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach($sucs as $s): ?>
          <div class="col">
            <a href="#" class="text-decoration-none" onclick="openSucursalModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nombre'], ENT_QUOTES) ?>')">
              <div class="card h-100 border-danger shadow-sm card-sucursal">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($s['nombre']) ?></h5>
                  <p class="card-text text-muted"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($s['direccion']) ?></p>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>

<!-- Modal para gestionar sucursal (usuarios) -->
<div class="modal fade" id="sucursalModal" tabindex="-1" aria-labelledby="sucursalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light text-danger border-bottom-0">
        <h5 class="modal-title" id="sucursalModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <!-- Columna para crear usuario -->
          <div class="col-md-5">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Crear Nuevo Usuario</h5>
                <form method="post">
                  <input type="hidden" name="action" value="create_user">
                  <div class="mb-3"><input name="nombre" class="form-control" placeholder="Nombre" required></div>
                  <div class="mb-3"><input name="correo" type="email" class="form-control" placeholder="Correo" required></div>
                  <div class="mb-3"><input name="password" type="password" class="form-control" placeholder="Contraseña" required></div>
                  <div class="mb-3">
                    <select name="rol" class="form-select" required>
                      <option value="">Elige un rol</option>
                      <option value="ADMIN">ADMIN</option>
                      <option value="CAJERO">CAJERO</option>
                      <option value="MOZO">MOZO</option>
                      <option value="COCINA">COCINA</option>
                      <option value="VENDEDOR">VENDEDOR</option>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-danger w-100">Crear Usuario</button>
                </form>
                <?php if (isset($_GET['success']) && $_GET['success'] === 'user_created'): ?>
                  <div class="alert alert-success mt-3" role="alert">
                    ¡Usuario creado con éxito!
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <!-- Columna para listar usuarios -->
          <div class="col-md-7">
            <h5>Usuarios en esta Empresa</h5>
            <div class="list-group">
              <?php if (empty($usersInEmpresa)): ?>
                <p class="text-muted">No hay usuarios registrados en esta empresa.</p>
              <?php else: ?>
                <?php foreach($usersInEmpresa as $user): ?>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1"><?= htmlspecialchars($user['nombre']) ?></h6>
                      <small class="badge bg-primary rounded-pill"><?= htmlspecialchars($user['rol']) ?></small>
                    </div>
                    <p class="mb-1 text-muted"><?= htmlspecialchars($user['correo']) ?></p>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const sucursalModal = new bootstrap.Modal(document.getElementById('sucursalModal'));

  function openSucursalModal(sucursalId, sucursalNombre) {
    document.getElementById('sucursalModalLabel').textContent = `Gestionar Sucursal: ${sucursalNombre}`;
    sucursalModal.show();
  }

  function toggleUserMenu() {
    const dropdown = document.getElementById('userMenuDropdown');
    if (dropdown) {
      dropdown.classList.toggle('show');
    }
  }

  document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userMenuDropdown');
    if (userMenu && !userMenu.contains(event.target)) {
      dropdown.classList.remove('show');
    }
  });
</script>
</body>
</html>
