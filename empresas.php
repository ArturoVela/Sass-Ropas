<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'SUPERadmin') {
  header('Location: index.php');
  exit;
}

// --- CONFIGURACIÓN API ---
define('API_BASE', 'http://ropas.spring.informaticapp.com:1655/api/ropas');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII');

// --- FUNCIONES API ---
function apiRequest($url, $method = 'GET', $data = null) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Authorization: Bearer ' . API_TOKEN
    ],
  ]);
  
  if ($data && in_array($method, ['POST', 'PUT'])) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  }
  
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  
  return [
    'status' => $httpCode,
    'data' => json_decode($response, true)
  ];
}

// --- PROCESAMIENTO DE FORMULARIOS ---
$message = '';
$messageType = '';

// Recoger mensajes de la sesión (de redirecciones)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'create_empresa':
        $empresaData = [
          'nombre' => $_POST['nombre'],
          'ruc' => $_POST['ruc'],
          'direccion' => $_POST['direccion'],
          'telefono' => $_POST['telefono'],
          'correo' => $_POST['correo'],
          'estado' => 1
        ];
        
        $result = apiRequest(API_BASE . '/empresas', 'POST', $empresaData);
        
        if ($result['status'] === 200 || $result['status'] === 201) {
          $message = 'Empresa creada exitosamente';
          $messageType = 'success';
        } else {
          $message = 'Error al crear empresa: ' . ($result['data']['message'] ?? 'Error desconocido');
          $messageType = 'danger';
        }
        break;
        
      case 'update_empresa':
        $empresaData = [
          'id' => (int)$_POST['id'],
          'nombre' => $_POST['nombre'],
          'ruc' => $_POST['ruc'],
          'direccion' => $_POST['direccion'],
          'telefono' => $_POST['telefono'],
          'correo' => $_POST['correo'],
          'estado' => (int)$_POST['estado']
        ];
        
        $result = apiRequest(API_BASE . '/empresas', 'PUT', $empresaData);
        
        if ($result['status'] === 200) {
          $message = 'Empresa actualizada exitosamente';
          $messageType = 'success';
        } else {
          $message = 'Error al actualizar empresa: ' . ($result['data']['message'] ?? 'Error desconocido');
          $messageType = 'danger';
        }
        break;
        
      case 'delete_empresa':
        $empresaId = $_POST['empresa_id'];
        $result = apiRequest(API_BASE . '/empresas/' . $empresaId, 'DELETE');
        
        if ($result['status'] === 200) {
          $message = 'Empresa eliminada exitosamente';
          $messageType = 'success';
        } else {
          $message = 'Error al eliminar empresa: ' . ($result['data']['message'] ?? 'Error desconocido');
          $messageType = 'danger';
        }
        break;
        
      case 'create_admin':
        $adminData = [
          'empresa' => ['id' => (int)$_POST['empresa_id']],
          'nombre' => $_POST['nombre'],
          'correo' => $_POST['correo'],
          'password' => $_POST['password'],
          'rol' => 'ADMIN',
          'estado' => 1
        ];
        
        $result = apiRequest(API_BASE . '/usuarios', 'POST', $adminData);
        
        if ($result['status'] === 200 || $result['status'] === 201) {
          $message = 'Usuario ADMIN creado exitosamente';
          $messageType = 'success';
        } else {
          $message = 'Error al crear usuario ADMIN: ' . ($result['data']['message'] ?? 'Error desconocido');
          $messageType = 'danger';
        }
        break;
    }
  }
}

// --- OBTENER EMPRESAS ---
$empresasResult = apiRequest(API_BASE . '/empresas');
$empresas = $empresasResult['data'] ?? [];

// --- OBTENER USUARIOS y crear mapa de Admins ---
$usuariosResult = apiRequest(API_BASE . '/usuarios');
$usuarios = $usuariosResult['data'] ?? [];
$adminsPorEmpresa = [];
foreach ($usuarios as $usuario) {
    if ($usuario['rol'] === 'ADMIN' && isset($usuario['empresa']['id'])) {
        $adminsPorEmpresa[$usuario['empresa']['id']] = $usuario;
    }
}

// No mostrar la empresa del SUPERadmin
$superadminEmpresaId = $_SESSION['user']['empresa']['id'] ?? null;
if ($superadminEmpresaId) {
    $empresas = array_filter($empresas, function($empresa) use ($superadminEmpresaId) {
        return $empresa['id'] != $superadminEmpresaId;
    });
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="CLÁSICO">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SUPERadmin | Empresas</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    /* — Variables y temas — */
    :root {
      --primary: #0d6efd;
      --primary-rgb: 13, 110, 253;
      --bg: #f8f9fa;
      --card-bg: #fff;
      --text: #212529;
      --clásico: #0d6efd;
    }
    [data-theme="OSCURO"]  { --primary: #212529; --primary-rgb: 33, 37, 41; --bg: #343a40; --card-bg: #495057; --text: #f8f9fa; }
    [data-theme="AZUL"]    { --primary: #0dcaf0; --primary-rgb: 13, 202, 240; --bg: #e7f5ff; --card-bg: #cff4fc; }
    [data-theme="ROJO"]    { --primary: #dc3545; --primary-rgb: 220, 53, 69; --bg: #f8d7da; --card-bg: #f5c2c7; }
    [data-theme="VERDE"]   { --primary: #198754; --primary-rgb: 25, 135, 84; --bg: #d1e7dd; --card-bg: #a3cfbb; }
    [data-theme="MORADO"]  { --primary: #6f42c1; --primary-rgb: 111, 66, 193; --bg: #e2d8f9; --card-bg: #cabdf0; }
    [data-theme="NARANJA"] { --primary: #fd7e14; --primary-rgb: 253, 126, 20; --bg: #fff4e6; --card-bg: #ffe5d0; }
    [data-theme="GRIS"]    { --primary: #6c757d; --primary-rgb: 108, 117, 125; --bg: #e9ecef; --card-bg: #dee2e6; }

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
    .modal-content {
      border-radius: 2rem 0.5rem 2rem 0.5rem;
      overflow: hidden;
      box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
    }
    .modal-header {
      background: var(--primary);
      color: #fff;
    }
    .modal-body {
      background: var(--card-bg);
    }

    /* — Formularios elegantes — */
    .form-elegant .form-control,
    .form-elegant .form-select {
      border-radius: 0.5rem;
      border: 1px solid #ddd;
      padding: 0.75rem 1rem;
    }
    .form-elegant .form-control:focus,
    .form-elegant .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.25rem rgba(var(--primary-rgb), 0.25);
    }
    .form-elegant label {
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    /* — Botones personalizados — */
    .btn-primary-custom {
      background-color: var(--primary);
      color: #fff;
      border: none;
      border-radius: 0.5rem;
      padding: 0.5rem 1rem;
      font-weight: 600;
    }
    .btn-primary-custom:hover {
      background-color: var(--primary);
      opacity: 0.9;
      color: #fff;
    }

    /* — Estadísticas — */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: var(--card-bg);
      border: 2px solid var(--primary);
      border-radius: 1rem;
      padding: 20px;
      text-align: center;
      transition: transform .2s, box-shadow .2s;
    }
    
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1);
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 5px;
    }
    
    .stat-label {
      color: var(--text);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.9rem;
    }

    /* — Búsqueda — */
    .search-container {
      background: var(--card-bg);
      border: 2px solid var(--primary);
      border-radius: 1rem;
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .search-input {
      max-width: 400px;
    }

    /* — Acciones de empresa — */
    .empresa-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    
    .empresa-actions .btn {
      border-radius: 0.5rem;
      font-size: 0.875rem;
      padding: 0.375rem 0.75rem;
    }

    /* — Información de empresa — */
    .empresa-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 15px;
    }
    
    .info-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .info-label {
      font-weight: 600;
      color: var(--text);
      min-width: 80px;
    }
    
    .info-value {
      color: var(--text);
    }

    /* — Badges — */
    .badge {
      border-radius: 20px;
      padding: 8px 15px;
      font-weight: 600;
    }
    
    /* Card link */
    .card-link {
      text-decoration: none;
      color: inherit;
    }
  </style>
</head>

<body>
  <!-- HEADER -->
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand" href="superadmin_dashboard.php">SUPERadmin</a>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link active" href="empresas.php">EMPRESAS</a></li>
          <li class="nav-item"><a class="nav-link" href="auditoria.php">AUDITORÍA</a></li>
        </ul>
        <!-- Theme & Profile -->
        <div class="d-flex align-items-center">
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
    </div>
  </nav>

  <div class="container py-4">
    <!-- Mensajes de alerta -->
    <?php if ($message): ?>
      <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Bienvenida -->
    <h1 class="welcome-title">
        <i class="bi bi-buildings me-2"></i>
        Gestión de Empresas
    </h1>
    <p>
        Crea, visualiza y administra las empresas y sus usuarios administradores.
    </p>

    <!-- Estadísticas -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-number"><?= count($empresas) ?></div>
          <div class="stat-label">Total Empresas</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= count(array_filter($empresas, fn($e) => $e['estado'] == 1)) ?></div>
          <div class="stat-label">Empresas Activas</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= count(array_filter($empresas, fn($e) => $e['estado'] == 0)) ?></div>
          <div class="stat-label">Empresas Inactivas</div>
        </div>
      </div>

    <!-- Barra de búsqueda y botón crear -->
      <div class="search-container">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="input-group">
              <span class="input-group-text" style="background-color: var(--primary); color: white;">
                <i class="bi bi-search"></i>
              </span>
              <input type="text" class="form-control" id="searchInput" placeholder="Buscar empresas...">
            </div>
          </div>
          <div class="col-md-4 text-end">
            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createEmpresaModal">
              <i class="bi bi-plus-circle me-2"></i>
              Crear Empresa
            </button>
          </div>
        </div>
      </div>

    <!-- Lista de Empresas -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="empresasContainer">
      <?php foreach ($empresas as $empresa): ?>
        <div class="col empresa-item" 
             data-nombre="<?= strtolower(htmlspecialchars($empresa['nombre'])) ?>"
             data-ruc="<?= htmlspecialchars($empresa['ruc']) ?>"
             data-correo="<?= strtolower(htmlspecialchars($empresa['correo'])) ?>">
          
          <div class="card emp-card h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <h5 class="mb-0">
                <i class="bi bi-building me-2"></i>
                <?= htmlspecialchars($empresa['nombre']) ?>
              </h5>
              <div class="empresa-actions">
                <a href="entrar_empresa.php?id=<?= $empresa['id'] ?>" class="btn btn-outline-primary btn-sm" title="Entrar a la Empresa">
                    <i class="bi bi-box-arrow-in-right"></i>
                </a>
                <button class="btn btn-outline-info btn-sm" 
                        onclick="viewEmpresa(<?= htmlspecialchars(json_encode($empresa)) ?>)"
                        title="Ver detalles">
                  <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-outline-warning btn-sm" 
                        onclick="editEmpresa(<?= htmlspecialchars(json_encode($empresa)) ?>)"
                        title="Editar">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="empresa_usuarios.php?id=<?= $empresa['id'] ?>" class="btn btn-outline-success btn-sm" title="Gestionar Usuarios">
                  <i class="bi bi-people"></i>
                </a>
                <button class="btn btn-outline-danger btn-sm" 
                        onclick="deleteEmpresa(<?= $empresa['id'] ?>, '<?= htmlspecialchars($empresa['nombre']) ?>')"
                        title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
            
            <div class="empresa-info">
              <div class="info-item">
                <span class="info-label">RUC:</span>
                <span class="info-value"><?= htmlspecialchars($empresa['ruc']) ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Teléfono:</span>
                <span class="info-value"><?= htmlspecialchars($empresa['telefono']) ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Correo:</span>
                <span class="info-value"><?= htmlspecialchars($empresa['correo']) ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Dirección:</span>
                <span class="info-value"><?= htmlspecialchars($empresa['direccion']) ?></span>
              </div>
            </div>
            
            <div class="text-center mt-3">
              <span class="badge bg-<?= $empresa['estado'] == 1 ? 'success' : 'danger' ?>">
                <i class="bi bi-<?= $empresa['estado'] == 1 ? 'check-circle' : 'x-circle' ?> me-1"></i>
                <?= $empresa['estado'] == 1 ? 'Activa' : 'Inactiva' ?>
              </span>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

    <!-- Mensaje cuando no hay empresas -->
    <?php if (empty($empresas)): ?>
      <div class="text-center py-5">
        <i class="bi bi-building fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No hay empresas registradas</h4>
        <p class="text-muted">Comienza creando la primera empresa</p>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createEmpresaModal">
          <i class="bi bi-plus-circle me-2"></i>
          Crear Primera Empresa
        </button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Modal Crear Empresa -->
  <div class="modal fade" id="createEmpresaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-plus-circle me-2"></i>
            Crear Nueva Empresa
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" class="form-elegant">
          <div class="modal-body">
            <input type="hidden" name="action" value="create_empresa">
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre de la Empresa *</label>
                <input type="text" class="form-control" name="nombre" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">RUC *</label>
                <input type="text" class="form-control" name="ruc" required maxlength="11">
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Teléfono *</label>
                <input type="tel" class="form-control" name="telefono" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Correo Electrónico *</label>
                <input type="email" class="form-control" name="correo" required>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Dirección *</label>
              <textarea class="form-control" name="direccion" rows="3" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary-custom">
              <i class="bi bi-save me-2"></i>
              Crear Empresa
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Editar Empresa -->
  <div class="modal fade" id="editEmpresaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-pencil me-2"></i>
            Editar Empresa
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" class="form-elegant">
          <div class="modal-body">
            <input type="hidden" name="action" value="update_empresa">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre de la Empresa *</label>
                <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">RUC *</label>
                <input type="text" class="form-control" name="ruc" id="edit_ruc" required maxlength="11">
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Teléfono *</label>
                <input type="tel" class="form-control" name="telefono" id="edit_telefono" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Correo Electrónico *</label>
                <input type="email" class="form-control" name="correo" id="edit_correo" required>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-8 mb-3">
                <label class="form-label">Dirección *</label>
                <textarea class="form-control" name="direccion" id="edit_direccion" rows="3" required></textarea>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado" id="edit_estado">
                  <option value="1">Activa</option>
                  <option value="0">Inactiva</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary-custom">
              <i class="bi bi-save me-2"></i>
              Actualizar Empresa
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Ver Empresa -->
  <div class="modal fade" id="viewEmpresaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-eye me-2"></i>
            Detalles de la Empresa
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold text-primary">Nombre:</label>
              <p class="form-control-plaintext" id="view_nombre"></p>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold text-primary">RUC:</label>
              <p class="form-control-plaintext" id="view_ruc"></p>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold text-primary">Teléfono:</label>
              <p class="form-control-plaintext" id="view_telefono"></p>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold text-primary">Correo:</label>
              <p class="form-control-plaintext" id="view_correo"></p>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold text-primary">Dirección:</label>
            <p class="form-control-plaintext" id="view_direccion"></p>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold text-primary">Estado:</label>
            <p class="form-control-plaintext" id="view_estado"></p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Confirmar Eliminación -->
  <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Confirmar Eliminación
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0">¿Estás seguro de que deseas eliminar la empresa <strong id="delete_empresa_nombre"></strong>?</p>
          <p class="text-danger small mt-2">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Esta acción no se puede deshacer.
          </p>
        </div>
        <form method="POST">
          <input type="hidden" name="action" value="delete_empresa">
          <input type="hidden" name="empresa_id" id="delete_empresa_id">
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">
              <i class="bi bi-trash me-2"></i>
              Eliminar Empresa
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
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

    // Búsqueda en tiempo real
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const empresas = document.querySelectorAll('.empresa-item');
        
        empresas.forEach(empresa => {
          const nombre = empresa.dataset.nombre;
          const ruc = empresa.dataset.ruc;
          const correo = empresa.dataset.correo;
          
          if (nombre.includes(searchTerm) || ruc.includes(searchTerm) || correo.includes(searchTerm)) {
            empresa.style.display = 'block';
          } else {
            empresa.style.display = 'none';
          }
        });
      });
    }

    // Función para editar empresa
    function editEmpresa(empresa) {
      document.getElementById('edit_id').value = empresa.id;
      document.getElementById('edit_nombre').value = empresa.nombre;
      document.getElementById('edit_ruc').value = empresa.ruc;
      document.getElementById('edit_telefono').value = empresa.telefono;
      document.getElementById('edit_correo').value = empresa.correo;
      document.getElementById('edit_direccion').value = empresa.direccion;
      document.getElementById('edit_estado').value = empresa.estado;
      
      new bootstrap.Modal(document.getElementById('editEmpresaModal')).show();
    }

    // Función para ver empresa
    function viewEmpresa(empresa) {
      document.getElementById('view_nombre').textContent = empresa.nombre;
      document.getElementById('view_ruc').textContent = empresa.ruc;
      document.getElementById('view_telefono').textContent = empresa.telefono;
      document.getElementById('view_correo').textContent = empresa.correo;
      document.getElementById('view_direccion').textContent = empresa.direccion;
      document.getElementById('view_estado').textContent = empresa.estado == 1 ? 'Activa' : 'Inactiva';
      
      new bootstrap.Modal(document.getElementById('viewEmpresaModal')).show();
    }

    // Función para eliminar empresa
    function deleteEmpresa(empresaId, empresaNombre) {
      document.getElementById('delete_empresa_id').value = empresaId;
      document.getElementById('delete_empresa_nombre').textContent = empresaNombre;
      
      new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      });
    }, 5000);
  </script>
</body>
</html> 