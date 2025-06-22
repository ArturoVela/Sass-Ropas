<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

// Se recuperan los datos del usuario y la empresa de la sesión
$user    = $_SESSION['user'];
$empId   = $user['empresa']['id'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);

require_once 'config_colors.php';

// --- Llamada al endpoint para obtener el historial ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/historialpuntos',
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

$historialCompleto = json_decode($response, true);
if (!is_array($historialCompleto)) $historialCompleto = [];

// --- Filtrado para mostrar solo el historial de la empresa actual ---
$historialEmpresa = array_filter($historialCompleto, fn($h) => isset($h['clienteId']['empresaId']['id']) && $h['clienteId']['empresaId']['id'] == $empId);

// --- Cálculo de estadísticas ---
$total_transacciones = count($historialEmpresa);
$puntos_acumulados = array_sum(array_column(array_filter($historialEmpresa, fn($h) => $h['tipo'] === 'acumulacion'), 'puntos'));
$puntos_canjeados = array_sum(array_column(array_filter($historialEmpresa, fn($h) => $h['tipo'] === 'canje'), 'puntos'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historial de Puntos | <?= $empName ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="css/dashboard.css" rel="stylesheet"/>
  <link href="css/puntos-clientes.css" rel="stylesheet"/>
  <style>
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
    .page-title {
      color: <?= $brandColor ?> !important;
    }
  </style>
</head>
<body class="bg-light">

  <?php include 'dashboard_sidebar.php'; ?>

<div class="d-flex">
  <main class="content flex-grow-1 p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="display-6 fw-bold page-title">
        <i class="bi bi-clock-history me-2"></i>Historial de Puntos
      </h1>
      <button id="exportBtn" class="btn btn-success d-flex align-items-center">
        <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel
      </button>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card stats-card border-0 shadow-sm">
          <div class="card-body text-center">
            <i class="bi bi-arrow-left-right text-primary fs-1"></i>
            <h4 class="mt-2 fw-bold"><?= number_format($total_transacciones) ?></h4>
            <p class="text-muted mb-0">Total Transacciones</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stats-card border-0 shadow-sm">
          <div class="card-body text-center">
            <i class="bi bi-plus-circle-fill text-success fs-1"></i>
            <h4 class="mt-2 fw-bold"><?= number_format($puntos_acumulados) ?></h4>
            <p class="text-muted mb-0">Puntos Acumulados</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card stats-card border-0 shadow-sm">
          <div class="card-body text-center">
            <i class="bi bi-dash-circle-fill text-danger fs-1"></i>
            <h4 class="mt-2 fw-bold"><?= number_format($puntos_canjeados) ?></h4>
            <p class="text-muted mb-0">Puntos Canjeados</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de Historial -->
    <div class="card shadow-sm">
      <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-danger-emphasis">Movimientos Registrados</h5>
        <div class="col-md-4">
          <input type="text" id="searchInput" class="form-control" placeholder="Buscar por cliente, tipo, descripción...">
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="text-center">
                <th>#</th>
                <th class="text-start">Cliente</th>
                <th>Tipo</th>
                <th>Puntos</th>
                <th class="text-start">Descripción</th>
                <th>Fecha</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="historial-table-body">
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

<!-- Modal: Ver Detalle del Historial -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light text-danger border-bottom-0">
        <h5 class="modal-title" id="viewModalLabel"><i class="bi bi-clock-history me-2"></i>Detalle del Movimiento</h5>
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
  const historialData = <?php echo json_encode(array_values($historialEmpresa)); ?>;
  const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));

  // --- Lógica de Búsqueda y Paginación ---
  const searchInput = document.getElementById('searchInput');
  const tableBody = document.getElementById('historial-table-body');
  const paginationContainer = document.getElementById('pagination-container');
  const rowsPerPage = 15;
  let currentPage = 1;
  let filteredData = [...historialData];

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
      let tipoBadge;
      if (row.tipo === 'acumulacion') {
        tipoBadge = `<span class="badge bg-success-subtle text-success-emphasis rounded-pill">Acumulación</span>`;
      } else if (row.tipo === 'canje') {
        tipoBadge = `<span class="badge bg-danger-subtle text-danger-emphasis rounded-pill">Canje</span>`;
      } else {
        tipoBadge = `<span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">${row.tipo}</span>`;
      }

      const puntosClass = row.tipo === 'acumulacion' ? 'text-success' : 'text-danger';
      const puntosIcon = row.tipo === 'acumulacion' ? '+' : '-';

      const rowHtml = `
        <tr>
          <td class="text-center fw-bold">${globalIndex}</td>
          <td>${row.clienteId.nombre}</td>
          <td class="text-center">${tipoBadge}</td>
          <td class="text-center">
            <span class="fw-bold ${puntosClass}">${puntosIcon}${row.puntos}</span>
          </td>
          <td>${row.descripcion || 'Sin descripción'}</td>
          <td class="text-center">${new Date(row.fecha).toLocaleDateString()}</td>
          <td class="text-center">
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
    paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">Anterior</a></li>`;
    for (let i = 1; i <= pageCount; i++) {
      paginationHtml += `<li class="page-item ${currentPage === i ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    paginationHtml += `<li class="page-item ${currentPage === pageCount ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">Siguiente</a></li>`;
    paginationHtml += '</ul>';
    paginationContainer.innerHTML = paginationHtml;
  }

  function handleSearch() {
    const searchTerm = searchInput.value.toLowerCase();
    filteredData = historialData.filter(row => {
      return row.clienteId.nombre.toLowerCase().includes(searchTerm) ||
             row.tipo.toLowerCase().includes(searchTerm) ||
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
    return historialData.find(h => h.id == id);
  }

  function openViewModal(id) {
    const record = findRecordById(id);
    if (!record) return;

    const cliente = record.clienteId;
    const empresa = cliente.empresaId;
    
    let tipoBadge;
    if (record.tipo === 'acumulacion') {
      tipoBadge = '<span class="badge bg-success">Acumulación</span>';
    } else if (record.tipo === 'canje') {
      tipoBadge = '<span class="badge bg-danger">Canje</span>';
    } else {
      tipoBadge = `<span class="badge bg-secondary">${record.tipo}</span>`;
    }

    const puntosClass = record.tipo === 'acumulacion' ? 'text-success' : 'text-danger';
    const puntosIcon = record.tipo === 'acumulacion' ? '+' : '-';
    
    let modalBody = `
      <div class="row">
        <div class="col-md-6">
          <h5>Datos del Cliente</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Nombre:</strong> ${cliente.nombre}</li>
            <li class="list-group-item"><strong>Documento:</strong> ${cliente.tipoDocumento} ${cliente.numeroDocumento}</li>
            <li class="list-group-item"><strong>Teléfono:</strong> ${cliente.telefono}</li>
            <li class="list-group-item"><strong>Correo:</strong> ${cliente.correo}</li>
          </ul>
        </div>
        <div class="col-md-6">
          <h5>Detalles del Movimiento</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Tipo:</strong> ${tipoBadge}</li>
            <li class="list-group-item"><strong>Puntos:</strong> <span class="fw-bold ${puntosClass}">${puntosIcon}${record.puntos}</span></li>
            <li class="list-group-item"><strong>Descripción:</strong> ${record.descripcion || 'Sin descripción'}</li>
            <li class="list-group-item"><strong>Fecha:</strong> ${new Date(record.fecha).toLocaleString()}</li>
          </ul>
          <h5 class="mt-3">Datos de la Empresa</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Empresa:</strong> ${empresa.nombre}</li>
            <li class="list-group-item"><strong>RUC:</strong> ${empresa.ruc}</li>
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
      "ID", "Cliente", "Tipo Documento", "Numero Documento", "Tipo Movimiento", 
      "Puntos", "Descripción", "Fecha", "Empresa"
    ];

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n";

    historialData.forEach(record => {
      const cliente = record.clienteId;
      const empresa = cliente.empresaId;
      const tipo = record.tipo === 'acumulacion' ? 'Acumulación' : (record.tipo === 'canje' ? 'Canje' : record.tipo);
      
      // Limpiamos los datos para evitar problemas con comas dentro de los campos
      const nombreLimpio = `"${cliente.nombre}"`;
      const descripcionLimpia = `"${record.descripcion || 'Sin descripción'}"`;
      
      const row = [
        record.id,
        nombreLimpio,
        cliente.tipoDocumento,
        cliente.numeroDocumento,
        tipo,
        record.puntos,
        descripcionLimpia,
        new Date(record.fecha).toLocaleString(),
        empresa.nombre
      ].join(",");
      
      csvContent += row + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "historial_puntos.csv");
    document.body.appendChild(link);
    
    link.click();
    
    document.body.removeChild(link);
  }
</script>
</body>
</html> 