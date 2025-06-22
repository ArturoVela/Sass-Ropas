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

require_once 'config_colors.php';

// --- Lógica de edición POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Actualizar datos de la recompensa
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/recompensas',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "id": '.$_POST['id'].',
            "nombre": "'.$_POST['nombre'].'",
            "descripcion": "'.$_POST['descripcion'].'",
            "puntos_requeridos": '.$_POST['puntos_requeridos'].',
            "stock": '.$_POST['stock'].',
            "estado": '.$_POST['estado'].'
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
            "evento": "EDICIÓN DE RECOMPENSA",
            "descripcion": "Se editó recompensa ID: '.$_POST['id'].' - Nombre: '.$_POST['nombre'].' - Puntos: '.$_POST['puntos_requeridos'].' - Stock: '.$_POST['stock'].' - Estado: '.($_POST['estado'] == 1 ? 'Activo' : 'Inactivo').'",
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
    
    header("Location: recompensas.php");
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && !isset($_POST['id'])) {
    // Crear nueva recompensa
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/recompensas',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "nombre": "'.$_POST['nombre'].'",
            "descripcion": "'.$_POST['descripcion'].'",
            "puntos_requeridos": '.$_POST['puntos_requeridos'].',
            "stock": '.$_POST['stock'].',
            "estado": '.$_POST['estado'].'
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
            "evento": "CREACIÓN DE RECOMPENSA",
            "descripcion": "Se creó nueva recompensa: '.$_POST['nombre'].' - Puntos requeridos: '.$_POST['puntos_requeridos'].' - Stock: '.$_POST['stock'].' - Estado: '.($_POST['estado'] == 1 ? 'Activo' : 'Inactivo').'",
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
    
    header("Location: recompensas.php");
    exit;
}

// --- Llamada al endpoint para obtener todas las recompensas ---
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
$curlError = curl_error($curl);
curl_close($curl);

$recompensas = json_decode($response, true);
if (!is_array($recompensas)) $recompensas = [];

// --- Cálculo de estadísticas ---
$total_recompensas = count($recompensas);
$total_stock = array_sum(array_column($recompensas, 'stock'));
$recompensa_mas_cara = 0;
if (!empty($recompensas)) {
    $puntos = array_column($recompensas, 'puntos_requeridos');
    $recompensa_mas_cara = max($puntos);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Recompensas | <?= $empName ?></title>
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
    .page-title {
      color: <?= $brandColor ?> !important;
    }
  </style>
</head>
<body class="bg-light">

  <?php include 'dashboard_sidebar.php'; ?>

  <!-- Vista normal de la lista -->
  <div class="d-flex">
    <main class="content flex-grow-1 p-4">

      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 text-danger fw-bold page-title">
          <i class="bi bi-gift-fill me-2"></i>Gestión de Recompensas
        </h1>
        <div>
          <button class="btn btn-primary d-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle-fill me-1"></i> Nueva Recompensa
          </button>
          <button id="exportBtn" class="btn btn-success d-flex align-items-center">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel
          </button>
        </div>
      </div>

      <!-- Tarjetas de Estadísticas -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-box2-heart-fill text-primary fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($total_recompensas) ?></h4>
              <p class="text-muted mb-0">Total de Recompensas</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-boxes text-success fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($total_stock) ?></h4>
              <p class="text-muted mb-0">Stock Total Disponible</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-star-fill text-warning fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($recompensa_mas_cara) ?></h4>
              <p class="text-muted mb-0">Puntos Máximos Requeridos</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Recompensas -->
      <div class="card shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-danger-emphasis">Listado de Recompensas</h5>
          <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o descripción...">
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="text-center">
                  <th>#</th>
                  <th class="text-start">Nombre</th>
                  <th class="text-start">Descripción</th>
                  <th>Puntos Requeridos</th>
                  <th>Stock</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="recompensas-table-body">
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

  <!-- Modal: Crear Recompensa -->
  <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-light text-danger border-bottom-0">
            <h5 class="modal-title" id="createModalLabel"><i class="bi bi-plus-circle-fill me-2"></i>Nueva Recompensa</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <label class="form-label fw-bold">Nombre de la Recompensa</label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Descuento 20%" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Puntos Requeridos</label>
                <input type="number" name="puntos_requeridos" class="form-control" placeholder="Ej: 100" required>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">Stock Disponible</label>
                <input type="number" name="stock" class="form-control" placeholder="Ej: 50" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Estado</label>
                <select name="estado" class="form-select">
                  <option value="1" selected>Activo</option>
                  <option value="0">Inactivo</option>
                </select>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-md-12">
                <label class="form-label fw-bold">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción detallada de la recompensa"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success">Crear Recompensa</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Editar Recompensa -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-light text-danger border-bottom-0">
            <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-square me-2"></i>Editar Recompensa</h5>
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
          <h5 class="modal-title" id="viewModalLabel"><i class="bi bi-gift-fill me-2"></i>Detalle de Recompensa</h5>
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
    const recompensasData = <?php echo json_encode(array_values($recompensas)); ?>;
    const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));

    // --- Lógica de Búsqueda y Paginación ---
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('recompensas-table-body');
    const paginationContainer = document.getElementById('pagination-container');
    const rowsPerPage = 15;
    let currentPage = 1;
    let filteredData = [...recompensasData];

    function renderTable() {
      tableBody.innerHTML = '';
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const paginatedData = filteredData.slice(start, end);

      if (paginatedData.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              <i class="bi bi-search fs-2"></i>
              <p class="mt-2 mb-0">No se encontraron resultados.</p>
            </td>
          </tr>`;
        return;
      }

      paginatedData.forEach((row, index) => {
        const globalIndex = start + index + 1;
        const estadoBadge = row.estado == 1
          ? `<span class="badge badge-estado activo">Activo</span>`
          : `<span class="badge badge-estado inactivo">Inactivo</span>`;

        const stockClass = row.stock > 10 ? 'text-success' : (row.stock > 0 ? 'text-warning' : 'text-danger');
        const stockIcon = row.stock > 10 ? 'bi-check-circle-fill' : (row.stock > 0 ? 'bi-exclamation-circle-fill' : 'bi-x-circle-fill');

        const rowHtml = `
          <tr>
            <td class="text-center fw-bold">${globalIndex}</td>
            <td>${row.nombre}</td>
            <td>${row.descripcion || 'Sin descripción'}</td>
            <td class="text-center">
              <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">${row.puntos_requeridos}</span>
            </td>
            <td class="text-center">
              <span class="${stockClass}">
                <i class="bi ${stockIcon} me-1"></i>${row.stock}
              </span>
            </td>
            <td class="text-center">${estadoBadge}</td>
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
      filteredData = recompensasData.filter(row => {
        return row.nombre.toLowerCase().includes(searchTerm) ||
               (row.descripcion && row.descripcion.toLowerCase().includes(searchTerm));
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
      return recompensasData.find(r => r.id == id);
    }

    function openEditModal(id) {
      const record = findRecordById(id);
      if (!record) return;

      let modalBody = `
        <input type="hidden" name="id" value="${record.id}">
        <div class="row">
          <div class="col-md-6">
            <label class="form-label fw-bold">Nombre de la Recompensa</label>
            <input type="text" name="nombre" class="form-control" value="${record.nombre}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Puntos Requeridos</label>
            <input type="number" name="puntos_requeridos" class="form-control" value="${record.puntos_requeridos}" required>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Stock Disponible</label>
            <input type="number" name="stock" class="form-control" value="${record.stock}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Estado</label>
            <select name="estado" class="form-select">
              <option value="1" ${record.estado == 1 ? 'selected' : ''}>Activo</option>
              <option value="0" ${record.estado == 0 ? 'selected' : ''}>Inactivo</option>
            </select>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-12">
            <label class="form-label fw-bold">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3">${record.descripcion || ''}</textarea>
          </div>
        </div>
      `;
      
      document.getElementById('editModalBody').innerHTML = modalBody;
      editModal.show();
    }

    function openViewModal(id) {
      const record = findRecordById(id);
      if (!record) return;

      const stockClass = record.stock > 10 ? 'text-success' : (record.stock > 0 ? 'text-warning' : 'text-danger');
      const stockIcon = record.stock > 10 ? 'bi-check-circle-fill' : (record.stock > 0 ? 'bi-exclamation-circle-fill' : 'bi-x-circle-fill');
      const stockStatus = record.stock > 10 ? 'Disponible' : (record.stock > 0 ? 'Stock Bajo' : 'Agotado');
      
      let modalBody = `
        <div class="row">
          <div class="col-md-6">
            <h5>Información General</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><strong>Nombre:</strong> ${record.nombre}</li>
              <li class="list-group-item"><strong>Descripción:</strong> ${record.descripcion || 'Sin descripción'}</li>
              <li class="list-group-item"><strong>Estado:</strong> ${record.estado == 1 ? '<span class="badge bg-primary">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'}</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h5>Detalles de Puntos y Stock</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><strong>Puntos Requeridos:</strong> <span class="badge bg-warning">${record.puntos_requeridos}</span></li>
              <li class="list-group-item"><strong>Stock Disponible:</strong> <span class="${stockClass}"><i class="bi ${stockIcon} me-1"></i>${record.stock}</span></li>
              <li class="list-group-item"><strong>Estado del Stock:</strong> <span class="badge ${record.stock > 10 ? 'bg-success' : (record.stock > 0 ? 'bg-warning' : 'bg-danger')}">${stockStatus}</span></li>
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
        "ID", "Nombre", "Descripción", "Puntos Requeridos", "Stock", "Estado"
      ];

      let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n";

      recompensasData.forEach(record => {
        const estado = record.estado == 1 ? "Activo" : "Inactivo";
        
        // Limpiamos los datos para evitar problemas con comas dentro de los campos
        const nombreLimpio = `"${record.nombre}"`;
        const descripcionLimpia = `"${record.descripcion || 'Sin descripción'}"`;
        
        const row = [
          record.id,
          nombreLimpio,
          descripcionLimpia,
          record.puntos_requeridos,
          record.stock,
          estado
        ].join(",");
        
        csvContent += row + "\n";
      });

      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "recompensas.csv");
      document.body.appendChild(link);
      
      link.click();
      
      document.body.removeChild(link);
    }
  </script>

</body>
</html> 