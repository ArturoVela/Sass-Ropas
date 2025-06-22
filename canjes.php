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

// --- Lógica de edición POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Actualizar datos del canje
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/canjes',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "id": '.$_POST['id'].',
            "cliente_id": '.$_POST['cliente_id'].',
            "recompensa_id": '.$_POST['recompensa_id'].',
            "fecha": "'.$_POST['fecha'].'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    
    // --- Registrar en Auditoría ---
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
            "evento": "EDICIÓN DE CANJE",
            "descripcion": "Se editó canje ID: '.$_POST['id'].' - Cliente ID: '.$_POST['cliente_id'].' - Recompensa ID: '.$_POST['recompensa_id'].' - Fecha: '.$_POST['fecha'].'",
            "fecha": "'.date('Y-m-d\TH:i:s').'",
            "estado": 1
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
        ),
    ));
    curl_exec($curl);
    curl_close($curl);
    
    header("Location: canjes.php");
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cliente_id']) && !isset($_POST['id'])) {
    // Crear nuevo canje
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/canjes',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "cliente_id": '.$_POST['cliente_id'].',
            "recompensa_id": '.$_POST['recompensa_id'].',
            "fecha": "'.date('Y-m-d\TH:i:s').'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    
    // --- Registrar en Auditoría ---
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
            "evento": "CREACIÓN DE CANJE",
            "descripcion": "Se creó nuevo canje - Cliente ID: '.$_POST['cliente_id'].' - Recompensa ID: '.$_POST['recompensa_id'].'",
            "fecha": "'.date('Y-m-d\TH:i:s').'",
            "estado": 1
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
        ),
    ));
    curl_exec($curl);
    curl_close($curl);
    
    header("Location: canjes.php");
    exit;
}

// --- Llamada al endpoint para obtener todos los canjes ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/canjes',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
  ),
));
$response = curl_exec($curl);
$curlError = curl_error($curl);
curl_close($curl);

$canjesCompletos = json_decode($response, true);
if (!is_array($canjesCompletos)) $canjesCompletos = [];

// --- Filtrado para mostrar solo los canjes de la empresa actual ---
$canjesEmpresa = array_filter($canjesCompletos, fn($c) => isset($c['cliente_id']['empresaId']['id']) && $c['cliente_id']['empresaId']['id'] == $empId);

// --- Llamada al endpoint de clientes para el formulario ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/puntosclientes',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
  ),
));
$response = curl_exec($curl);
curl_close($curl);

$clientesCompletos = json_decode($response, true);
if (!is_array($clientesCompletos)) $clientesCompletos = [];
$clientesEmpresa = array_filter($clientesCompletos, fn($c) => isset($c['clienteId']['empresaId']['id']) && $c['clienteId']['empresaId']['id'] == $empId);

// --- Llamada al endpoint de recompensas para el formulario ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/recompensas',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
  ),
));
$response = curl_exec($curl);
curl_close($curl);

$recompensasCompletas = json_decode($response, true);
if (!is_array($recompensasCompletas)) $recompensasCompletas = [];

// --- Cálculo de estadísticas ---
$total_canjes = count($canjesEmpresa);
$canjes_hoy = count(array_filter($canjesEmpresa, fn($c) => date('Y-m-d') === date('Y-m-d', strtotime($c['fecha']))));
$puntos_canjeados = array_sum(array_column($canjesEmpresa, 'recompensa.puntos_requeridos'));
$recompensa_mas_popular = '';
if (!empty($canjesEmpresa)) {
    $recompensas_count = [];
    foreach ($canjesEmpresa as $canje) {
        $recompensa_nombre = $canje['recompensa']['nombre'];
        $recompensas_count[$recompensa_nombre] = ($recompensas_count[$recompensa_nombre] ?? 0) + 1;
    }
    $recompensa_mas_popular = array_keys($recompensas_count, max($recompensas_count))[0] ?? 'N/A';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Canjes de Puntos | <?= $empName ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="css/dashboard.css" rel="stylesheet"/>
  <link href="css/puntos-clientes.css" rel="stylesheet"/>
  <style>
    @media (min-width: 768px) {
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
      #sidebarOffcanvas .offcanvas-body {
        display: flex;
        flex-direction: column;
        height: 100vh;
      }
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

      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 text-danger fw-bold">
          <i class="bi bi-gift-fill me-2"></i>Canjes de Puntos
        </h1>
        <div>
          <button class="btn btn-primary d-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle-fill me-1"></i> Nuevo Canje
          </button>
          <button id="exportBtn" class="btn btn-success d-flex align-items-center">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel
          </button>
        </div>
      </div>

      <!-- Tarjetas de Estadísticas -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-gift-fill text-primary fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($total_canjes) ?></h4>
              <p class="text-muted mb-0">Total de Canjes</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-calendar-check text-success fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($canjes_hoy) ?></h4>
              <p class="text-muted mb-0">Canjes Hoy</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-star-fill text-warning fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($puntos_canjeados) ?></h4>
              <p class="text-muted mb-0">Puntos Canjeados</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-trophy-fill text-info fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= htmlspecialchars($recompensa_mas_popular) ?></h4>
              <p class="text-muted mb-0">Recompensa Más Popular</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Canjes -->
      <div class="card shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-danger-emphasis">Listado de Canjes</h5>
          <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por cliente o recompensa...">
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="text-center">
                  <th>#</th>
                  <th class="text-start">Cliente</th>
                  <th class="text-start">Recompensa</th>
                  <th>Puntos Canjeados</th>
                  <th>Fecha</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="canjes-table-body">
                <!-- Las filas se inyectarán aquí con JS -->
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer bg-white border-0 d-flex justify-content-end">
          <nav id="pagination-container"></nav>
        </div>
      </div>
    </main>
  </div>

  <!-- Modal: Crear Canje -->
  <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-light text-danger border-bottom-0">
            <h5 class="modal-title" id="createModalLabel"><i class="bi bi-plus-circle-fill me-2"></i>Nuevo Canje</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <label class="form-label fw-bold">Cliente</label>
                <select name="cliente_id" class="form-select" required>
                  <option value="">Seleccionar Cliente</option>
                  <?php foreach ($clientesEmpresa as $cliente): ?>
                    <option value="<?= $cliente['clienteId']['id'] ?>">
                      <?= htmlspecialchars($cliente['clienteId']['nombre']) ?> - <?= htmlspecialchars($cliente['clienteId']['numeroDocumento']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Recompensa</label>
                <select name="recompensa_id" class="form-select" required>
                  <option value="">Seleccionar Recompensa</option>
                  <?php foreach ($recompensasCompletas as $recompensa): ?>
                    <option value="<?= $recompensa['id'] ?>">
                      <?= htmlspecialchars($recompensa['nombre']) ?> (<?= $recompensa['puntos_requeridos'] ?> pts)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success">Crear Canje</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Editar Canje -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-light text-danger border-bottom-0">
            <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-square me-2"></i>Editar Canje</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="editModalBody">
            <!-- El contenido se inyectará aquí con JS -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success">Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Ver Detalle -->
  <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-light text-danger border-bottom-0">
          <h5 class="modal-title" id="viewModalLabel"><i class="bi bi-gift-fill me-2"></i>Detalle del Canje</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="viewModalBody">
          <!-- El contenido se inyectará aquí con JS -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Pasamos los datos de PHP a JavaScript de forma segura
    const canjesData = <?php echo json_encode(array_values($canjesEmpresa)); ?>;
    const clientesData = <?php echo json_encode(array_values($clientesEmpresa)); ?>;
    const recompensasData = <?php echo json_encode(array_values($recompensasCompletas)); ?>;
    const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));

    // --- Lógica de Búsqueda y Paginación ---
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('canjes-table-body');
    const paginationContainer = document.getElementById('pagination-container');
    const rowsPerPage = 15;
    let currentPage = 1;
    let filteredData = [...canjesData];

    function renderTable() {
      tableBody.innerHTML = '';
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const paginatedData = filteredData.slice(start, end);

      if (paginatedData.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              <i class="bi bi-search fs-2"></i>
              <p class="mt-2 mb-0">No se encontraron resultados.</p>
            </td>
          </tr>`;
        return;
      }

      paginatedData.forEach((row, index) => {
        const globalIndex = start + index + 1;

        const rowHtml = `
          <tr>
            <td class="text-center fw-bold">${globalIndex}</td>
            <td>${row.cliente_id.nombre}</td>
            <td>${row.recompensa.nombre}</td>
            <td class="text-center">
              <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">${row.recompensa.puntos_requeridos}</span>
            </td>
            <td class="text-center">${new Date(row.fecha).toLocaleDateString()}</td>
            <td class="text-center">
              <button class="btn btn-outline-primary btn-sm" title="Editar" onclick="openEditModal(${row.id})">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-outline-secondary btn-sm" title="Ver detalle" onclick="openViewModal(${row.id})">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>`;
        tableBody.innerHTML += rowHtml;
      });
    }

    function renderPagination() {
      paginationContainer.innerHTML = '';
      const pageCount = Math.ceil(filteredData.length / rowsPerPage);
      if (pageCount <= 1) return;

      let paginationHtml = '<ul class="pagination mb-0">';
      
      // Botón "Anterior"
      paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">Anterior</a></li>`;

      // Botones de páginas
      for (let i = 1; i <= pageCount; i++) {
        paginationHtml += `<li class="page-item ${currentPage === i ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
      }

      // Botón "Siguiente"
      paginationHtml += `<li class="page-item ${currentPage === pageCount ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">Siguiente</a></li>`;
      
      paginationHtml += '</ul>';
      paginationContainer.innerHTML = paginationHtml;
    }

    function handleSearch() {
      const searchTerm = searchInput.value.toLowerCase();
      filteredData = canjesData.filter(row => {
        return row.cliente_id.nombre.toLowerCase().includes(searchTerm) ||
               row.recompensa.nombre.toLowerCase().includes(searchTerm);
      });
      currentPage = 1;
      renderTable();
      renderPagination();
    }

    function handlePaginationClick(e) {
      if (e.target.tagName === 'A' && !e.target.parentElement.classList.contains('disabled')) {
        e.preventDefault();
        currentPage = parseInt(e.target.dataset.page, 10);
        renderTable();
        renderPagination();
      }
    }

    // --- Funciones de Modal ---
    function findRecordById(id) {
      return canjesData.find(c => c.id == id);
    }

    function openEditModal(id) {
      const record = findRecordById(id);
      if (!record) return;

      let modalBody = `
        <input type="hidden" name="id" value="${record.id}">
        <div class="row">
          <div class="col-md-6">
            <label class="form-label fw-bold">Cliente</label>
            <select name="cliente_id" class="form-select" required>
              <option value="">Seleccionar Cliente</option>
              ${clientesData.map(cliente => `
                <option value="${cliente.clienteId.id}" ${record.cliente_id.id == cliente.clienteId.id ? 'selected' : ''}>
                  ${cliente.clienteId.nombre} - ${cliente.clienteId.numeroDocumento}
                </option>
              `).join('')}
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Recompensa</label>
            <select name="recompensa_id" class="form-select" required>
              <option value="">Seleccionar Recompensa</option>
              ${recompensasData.map(recompensa => `
                <option value="${recompensa.id}" ${record.recompensa.id == recompensa.id ? 'selected' : ''}>
                  ${recompensa.nombre} (${recompensa.puntos_requeridos} pts)
                </option>
              `).join('')}
            </select>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Fecha</label>
            <input type="datetime-local" name="fecha" class="form-control" value="${record.fecha.replace('T', ' ').substring(0, 16)}" required>
          </div>
        </div>
      `;
      
      document.getElementById('editModalBody').innerHTML = modalBody;
      editModal.show();
    }

    function openViewModal(id) {
      const record = findRecordById(id);
      if (!record) return;

      let modalBody = `
        <div class="row">
          <div class="col-md-6">
            <h5>Información del Cliente</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><strong>Nombre:</strong> ${record.cliente_id.nombre}</li>
              <li class="list-group-item"><strong>Documento:</strong> ${record.cliente_id.tipoDocumento} ${record.cliente_id.numeroDocumento}</li>
              <li class="list-group-item"><strong>Teléfono:</strong> ${record.cliente_id.telefono}</li>
              <li class="list-group-item"><strong>Correo:</strong> ${record.cliente_id.correo}</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h5>Información de la Recompensa</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><strong>Nombre:</strong> ${record.recompensa.nombre}</li>
              <li class="list-group-item"><strong>Descripción:</strong> ${record.recompensa.descripcion || 'Sin descripción'}</li>
              <li class="list-group-item"><strong>Puntos Requeridos:</strong> <span class="badge bg-warning">${record.recompensa.puntos_requeridos}</span></li>
              <li class="list-group-item"><strong>Fecha del Canje:</strong> ${new Date(record.fecha).toLocaleString()}</li>
            </ul>
          </div>
        </div>
      `;
      document.getElementById('viewModalBody').innerHTML = modalBody;
      viewModal.show();
    }

    // --- Inicialización y Eventos ---
    searchInput.addEventListener('keyup', handleSearch);
    paginationContainer.addEventListener('click', handlePaginationClick);
    document.getElementById('exportBtn').addEventListener('click', exportToExcel);
    
    // Render inicial
    renderTable();
    renderPagination();
    
    function exportToExcel() {
      const headers = [
        "ID", "Cliente", "Documento", "Recompensa", "Puntos Canjeados", "Fecha"
      ];

      let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n";

      canjesData.forEach(record => {
        // Limpiamos los datos para evitar problemas con comas dentro de los campos
        const clienteLimpio = `"${record.cliente_id.nombre}"`;
        const recompensaLimpia = `"${record.recompensa.nombre}"`;
        
        const row = [
          record.id,
          clienteLimpio,
          `${record.cliente_id.tipoDocumento} ${record.cliente_id.numeroDocumento}`,
          recompensaLimpia,
          record.recompensa.puntos_requeridos,
          new Date(record.fecha).toLocaleString()
        ].join(",");
        
        csvContent += row + "\n";
      });

      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "canjes.csv");
      document.body.appendChild(link);
      
      link.click();
      
      document.body.removeChild(link);
    }
  </script>

</body>
</html> 