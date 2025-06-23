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
    // Actualizar datos del punto
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/puntosclientes',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => '{
            "id": '.$_POST['id'].',
            "clienteId": '.$_POST['clienteId'].',
            "puntos_acumulados": '.$_POST['puntos_acumulados'].',
            "puntos_utilizados": '.$_POST['puntos_utilizados'].',
            "ultima_actualizacion": "'.date('Y-m-d\TH:i:s').'"
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
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/auditoria',
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
            "evento": "EDICIÓN DE PUNTOS",
            "descripcion": "Se editó puntos del cliente ID: '.$_POST['clienteId'].' - Puntos Acumulados: '.$_POST['puntos_acumulados'].' - Puntos Utilizados: '.$_POST['puntos_utilizados'].'",
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
    
    header("Location: PuntosClientes.php");
    exit;
}

// --- Llamada al endpoint para obtener todos los puntos ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/puntosclientes',
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

$puntosClientes = json_decode($response, true);
if (!is_array($puntosClientes)) $puntosClientes = [];

// --- Filtrado para mostrar solo los clientes de la empresa actual ---
$puntosEmpresa = array_filter($puntosClientes, fn($p) => isset($p['clienteId']['empresaId']['id']) && $p['clienteId']['empresaId']['id'] == $empId);

// --- Cálculo de estadísticas para las tarjetas de resumen ---
$total_clientes = count($puntosEmpresa);
$total_acumulados = array_sum(array_column($puntosEmpresa, 'puntos_acumulados'));
$total_utilizados = array_sum(array_column($puntosEmpresa, 'puntos_utilizados'));
$total_disponibles = $total_acumulados - $total_utilizados;

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Puntos de Clientes | <?= $empName ?></title>
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
  <?php include 'header_sidebar.php'; ?>

  <!-- Vista normal de la lista -->
  <div class="d-flex">
    <main class="content flex-grow-1 p-4">

      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 text-danger fw-bold page-title">
          <i class="bi bi-star-fill me-2"></i>Puntos de Clientes
        </h1>
        <button id="exportBtn" class="btn btn-success d-flex align-items-center">
          <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel
        </button>
      </div>

      <!-- Tarjetas de Estadísticas -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-people-fill text-primary fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= $total_clientes ?></h4>
              <p class="text-muted mb-0">Total Clientes</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-star-fill text-warning fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($total_acumulados) ?></h4>
              <p class="text-muted mb-0">Puntos Acumulados</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-patch-check-fill text-success fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($total_disponibles) ?></h4>
              <p class="text-muted mb-0">Puntos Disponibles</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-gift-fill text-info fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($total_utilizados) ?></h4>
              <p class="text-muted mb-0">Puntos Utilizados</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Clientes -->
      <div class="card shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-danger-emphasis">Listado de Clientes</h5>
          <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o documento...">
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="text-center">
                  <th>#</th>
                  <th class="text-start">Nombre</th>
                  <th class="text-start">Documento</th>
                  <th>P. Acumulados</th>
                  <th>P. Utilizados</th>
                  <th>Última Actualización</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="puntos-table-body">
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

  <!-- Modal: Editar Puntos -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-light text-danger border-bottom-0">
            <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-square me-2"></i>Editar Puntos de Cliente</h5>
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
          <h5 class="modal-title" id="viewModalLabel"><i class="bi bi-person-vcard-fill me-2"></i>Detalles del Cliente</h5>
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
    const puntosData = <?php echo json_encode(array_values($puntosEmpresa)); ?>;
    const API_BASE_URL = 'http://ropas.spring.informaticapp.com:1655/api/ropas';
    const API_TOKEN = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII';
    const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));

    // --- Lógica de Búsqueda y Paginación ---
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('puntos-table-body');
    const paginationContainer = document.getElementById('pagination-container');
    const rowsPerPage = 20;
    let currentPage = 1;
    let filteredData = [...puntosData];

    function renderTable() {
      tableBody.innerHTML = '';
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const paginatedData = filteredData.slice(start, end);

      if (paginatedData.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
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

        const rowHtml = `
          <tr>
            <td class="text-center fw-bold">${globalIndex}</td>
            <td>${row.clienteId.nombre}</td>
            <td>${row.clienteId.tipoDocumento}: ${row.clienteId.numeroDocumento}</td>
            <td class="text-center"><span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">${row.puntos_acumulados}</span></td>
            <td class="text-center"><span class="badge bg-info-subtle text-info-emphasis rounded-pill">${row.puntos_utilizados}</span></td>
            <td class="text-center">${new Date(row.ultima_actualizacion).toLocaleDateString()}</td>
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
      filteredData = puntosData.filter(row => {
        return row.clienteId.nombre.toLowerCase().includes(searchTerm) ||
               row.clienteId.numeroDocumento.toLowerCase().includes(searchTerm);
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
      return puntosData.find(p => p.id == id);
    }

    function openEditModal(id) {
      const record = findRecordById(id);
      if (!record) return;

      const cliente = record.clienteId;
      
      let modalBody = `
        <input type="hidden" name="id" value="${record.id}">
        <input type="hidden" name="clienteId" value="${cliente.id}">
        
        <div class="row mb-3">
          <div class="col-md-6">
            <h6 class="text-muted">Cliente</h6>
            <p class="mb-1"><strong>Nombre:</strong> ${cliente.nombre}</p>
            <p class="mb-0"><strong>Documento:</strong> ${cliente.tipoDocumento} ${cliente.numeroDocumento}</p>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted">Empresa</h6>
            <p class="mb-1"><strong>Nombre:</strong> ${cliente.empresaId.nombre}</p>
            <p class="mb-0"><strong>RUC:</strong> ${cliente.empresaId.ruc}</p>
          </div>
        </div>
        
        <hr>
        
        <div class="row">
          <div class="col-md-6">
            <label class="form-label fw-bold">Puntos Acumulados</label>
            <input type="number" name="puntos_acumulados" class="form-control" value="${record.puntos_acumulados}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Puntos Utilizados</label>
            <input type="number" name="puntos_utilizados" class="form-control" value="${record.puntos_utilizados}" required>
          </div>
        </div>
      `;
      
      document.getElementById('editModalBody').innerHTML = modalBody;
      editModal.show();
    }

    function openViewModal(id) {
      const record = findRecordById(id);
      if (!record) return;

      const cliente = record.clienteId;
      const empresa = cliente.empresaId;
      
      let modalBody = `
        <div class="row">
          <div class="col-md-6">
            <h5>Datos Personales</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><strong>Nombre:</strong> ${cliente.nombre}</li>
              <li class="list-group-item"><strong>Documento:</strong> ${cliente.tipoDocumento} ${cliente.numeroDocumento}</li>
              <li class="list-group-item"><strong>Teléfono:</strong> ${cliente.telefono}</li>
              <li class="list-group-item"><strong>Correo:</strong> ${cliente.correo}</li>
              <li class="list-group-item"><strong>Dirección:</strong> ${cliente.direccion}</li>
              <li class="list-group-item"><strong>Nacimiento:</strong> ${new Date(cliente.fechaNacimiento).toLocaleDateString()}</li>
              <li class="list-group-item"><strong>Género:</strong> ${cliente.genero}</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h5>Puntos y Estado</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><strong>Puntos Acumulados:</strong> <span class="badge bg-success">${record.puntos_acumulados}</span></li>
              <li class="list-group-item"><strong>Puntos Utilizados:</strong> <span class="badge bg-warning">${record.puntos_utilizados}</span></li>
              <li class="list-group-item"><strong>Última Actualización:</strong> ${new Date(record.ultima_actualizacion).toLocaleString()}</li>
              <li class="list-group-item"><strong>Estado:</strong> ${record.estado == 1 ? '<span class="badge bg-primary">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'}</li>
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
        "ID", "Nombre Cliente", "Tipo Documento", "Numero Documento", "Telefono", "Correo", 
        "Puntos Acumulados", "Puntos Utilizados", "Puntos Disponibles", "Estado", "Ultima Actualizacion"
      ];

      let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n";

      puntosData.forEach(record => {
        const cliente = record.clienteId;
        const estado = record.estado == 1 ? "Activo" : "Inactivo";
        const puntosDisponibles = record.puntos_acumulados - record.puntos_utilizados;
        
        // Limpiamos los datos para evitar problemas con comas dentro de los campos
        const nombreLimpio = `"${cliente.nombre}"`;
        
        const row = [
          record.id,
          nombreLimpio,
          cliente.tipoDocumento,
          cliente.numeroDocumento,
          cliente.telefono,
          cliente.correo,
          record.puntos_acumulados,
          record.puntos_utilizados,
          puntosDisponibles,
          estado,
          new Date(record.ultima_actualizacion).toLocaleString()
        ].join(",");
        
        csvContent += row + "\n";
      });

      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "puntos_clientes.csv");
      document.body.appendChild(link);
      
      link.click();
      
      document.body.removeChild(link);
    }
  </script>

</body>
</html>