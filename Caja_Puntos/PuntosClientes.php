<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

// Debug básico para verificar si se recibe POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("=== POST RECIBIDO ===");
    error_log("Datos POST: " . json_encode($_POST));
    if (isset($_POST['estado'])) {
        error_log("ESTADO RECIBIDO: " . $_POST['estado'] . " (tipo: " . gettype($_POST['estado']) . ")");
    } else {
        error_log("ERROR: Campo 'estado' NO está presente");
    }
    error_log("====================");
}

// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

$user    = $_SESSION['user'];
$empId   = $user['empresa']['id'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);

require_once 'config_colors.php';

// --- Lógica de edición POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Verificar que todos los campos requeridos estén presentes
    if (!isset($_POST['estado'])) {
        error_log("ERROR: Campo 'estado' no está presente en el formulario");
        error_log("Campos recibidos: " . implode(', ', array_keys($_POST)));
        header("Location: PuntosClientes.php");
        exit;
    }
    
    // Debug: Mostrar los datos recibidos
    error_log("Datos recibidos en edición: " . json_encode($_POST));
    error_log("Campo estado recibido: " . (isset($_POST['estado']) ? $_POST['estado'] : 'NO EXISTE'));
    error_log("Tipo de estado: " . (isset($_POST['estado']) ? gettype($_POST['estado']) : 'NO EXISTE'));
    error_log("Estado es string vacío?: " . (isset($_POST['estado']) && $_POST['estado'] === '' ? 'SÍ' : 'NO'));
    error_log("Estado es null?: " . (isset($_POST['estado']) && $_POST['estado'] === null ? 'SÍ' : 'NO'));
    
    // Validar datos
    $id = intval($_POST['id']);
    $clienteId = intval($_POST['clienteId']);
    $puntosAcumulados = intval($_POST['puntos_acumulados']);
    $puntosUtilizados = intval($_POST['puntos_utilizados']);
    $estado = intval($_POST['estado']);
    
    error_log("Estado después de intval: " . $estado);
    error_log("Estado es 0?: " . ($estado === 0 ? 'SÍ' : 'NO'));
    error_log("Estado es 1?: " . ($estado === 1 ? 'SÍ' : 'NO'));
    error_log("Estado en array [0,1]?: " . (in_array($estado, [0, 1], true) ? 'SÍ' : 'NO'));
    error_log("Estado es exactamente 0?: " . ($estado === 0 ? 'SÍ' : 'NO'));
    error_log("Estado es exactamente 1?: " . ($estado === 1 ? 'SÍ' : 'NO'));
    
    // Validaciones básicas
    error_log("Validando datos - ID: $id, ClienteID: $clienteId, PuntosAcum: $puntosAcumulados, PuntosUtil: $puntosUtilizados, Estado: $estado");
    
    if ($id <= 0) {
        error_log("ERROR: ID inválido");
        header("Location: PuntosClientes.php");
        exit;
    }
    
    if ($clienteId <= 0) {
        error_log("ERROR: ClienteID inválido");
        header("Location: PuntosClientes.php");
        exit;
    }
    
    if ($puntosAcumulados < 0) {
        error_log("ERROR: Puntos acumulados inválidos");
        header("Location: PuntosClientes.php");
        exit;
    }
    
    if ($puntosUtilizados < 0) {
        error_log("ERROR: Puntos utilizados inválidos");
        header("Location: PuntosClientes.php");
        exit;
    }
    
    if ($estado !== 0 && $estado !== 1) {
        error_log("ERROR: Estado inválido - debe ser 0 o 1, recibido: $estado");
        header("Location: PuntosClientes.php");
        exit;
    }
    
    error_log("Validación exitosa - procediendo con la actualización");
    
    // Actualizar datos del punto
    $jsonData = '{
        "id": '.$id.',
        "clienteId": '.$clienteId.',
        "puntos_acumulados": '.$puntosAcumulados.',
        "puntos_utilizados": '.$puntosUtilizados.',
        "estado": '.$estado.',
        "ultima_actualizacion": "'.date('Y-m-d\TH:i:s').'"
    }';
    
    error_log("JSON a enviar: " . $jsonData);
    
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
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
        ),
    ));
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    // Debug: Mostrar respuesta de la API
    error_log("=== RESPUESTA DE LA API ===");
    error_log("HTTP Code: " . $httpCode);
    error_log("CURL Error: " . $curlError);
    error_log("Respuesta: " . $response);
    error_log("JSON enviado: " . $jsonData);
    error_log("==========================");
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("API respondió exitosamente");
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
                "empresa": {"id":'.$user['empresa']['id'].'},
                "sucursal": {"id":'.($_SESSION['sucursal_seleccionada'] ?? 1).'},
                "evento": "EDICIÓN DE PUNTOS",
                "descripcion": "Se editó puntos del cliente ID: '.$_POST['clienteId'].' - Puntos Acumulados: '.$_POST['puntos_acumulados'].' - Puntos Utilizados: '.$_POST['puntos_utilizados'].' - Estado: '.($_POST['estado'] == 1 ? 'Activo' : 'Inactivo').'",
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
    } else {
        // Si hay error, redirigir sin mensaje
        header("Location: PuntosClientes.php");
        exit;
    }
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
          <div class="d-flex gap-2 align-items-center flex-grow-1" style="max-width: 700px; margin-left: auto;">
            <!-- Menú de ordenamiento -->
            <div class="dropdown me-2">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-sort-down me-1"></i>Ordenar por
              </button>
              <ul class="dropdown-menu">
                <li><h6 class="dropdown-header">Puntos Acumulados</h6></li>
                <li><a class="dropdown-item" href="#" data-sort="acumulados-desc">Mayor acumulación</a></li>
                <li><a class="dropdown-item" href="#" data-sort="acumulados-asc">Menor acumulación</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Puntos Utilizados</h6></li>
                <li><a class="dropdown-item" href="#" data-sort="utilizados-desc">Más utilizados</a></li>
                <li><a class="dropdown-item" href="#" data-sort="utilizados-asc">Menos utilizados</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Nombre</h6></li>
                <li><a class="dropdown-item" href="#" data-sort="nombre-asc">A-Z</a></li>
                <li><a class="dropdown-item" href="#" data-sort="nombre-desc">Z-A</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Estado</h6></li>
                <li><a class="dropdown-item" href="#" data-sort="estado-desc">Activos primero</a></li>
                <li><a class="dropdown-item" href="#" data-sort="estado-asc">Inactivos primero</a></li>
              </ul>
            </div>
            <!-- Buscador -->
            <input type="text" id="searchInput" class="form-control flex-grow-1 w-100" placeholder="Buscar por nombre o documento...">
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
        <form method="post" action="PuntosClientes.php">
          <div class="modal-header bg-light text-danger border-bottom-0">
            <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-square me-2"></i>Editar Puntos de Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="editModalBody">
            <!-- El contenido se inyectará aquí con JS -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success" id="btnGuardarCambios">Guardar Cambios</button>
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
    let currentSort = 'nombre-asc'; // Ordenamiento por defecto

    function sortData(sortType) {
        currentSort = sortType;
        const [field, direction] = sortType.split('-');
        
        filteredData.sort((a, b) => {
            let valueA, valueB;
            
            switch(field) {
                case 'acumulados':
                    valueA = a.puntos_acumulados;
                    valueB = b.puntos_acumulados;
                    break;
                case 'utilizados':
                    valueA = a.puntos_utilizados;
                    valueB = b.puntos_utilizados;
                    break;
                case 'nombre':
                    valueA = a.clienteId.nombre.toLowerCase();
                    valueB = b.clienteId.nombre.toLowerCase();
                    break;
                case 'estado':
                    valueA = a.estado;
                    valueB = b.estado;
                    break;
                default:
                    return 0;
            }
            
            if (direction === 'asc') {
                return valueA > valueB ? 1 : -1;
            } else {
                return valueA < valueB ? 1 : -1;
            }
        });
        
        currentPage = 1;
        renderTable();
        renderPagination();
    }

    function handleSortClick(e) {
        e.preventDefault();
        const sortType = e.target.dataset.sort;
        if (sortType) {
            sortData(sortType);
            
            // Actualizar el texto del botón para mostrar el ordenamiento actual
            const button = document.querySelector('.dropdown-toggle');
            const icon = button.querySelector('i');
            const text = e.target.textContent;
            button.innerHTML = `<i class="bi bi-sort-down me-1"></i>${text}`;
        }
    }

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
      
      // Aplicar el ordenamiento actual después de filtrar
      sortData(currentSort);
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
      
      // Debug: Mostrar los datos del registro en consola
      console.log('Datos del registro a editar:', record);
      console.log('Estado actual:', record.estado, 'Tipo:', typeof record.estado);
      
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
          <div class="col-md-4">
            <label class="form-label fw-bold">Puntos Acumulados</label>
            <input type="number" name="puntos_acumulados" class="form-control" value="${record.puntos_acumulados}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Puntos Utilizados</label>
            <input type="number" name="puntos_utilizados" class="form-control" value="${record.puntos_utilizados}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Estado</label>
            <select name="estado" class="form-select">
              <option value="1" ${parseInt(record.estado) === 1 ? 'selected' : ''}>Activo</option>
              <option value="0" ${parseInt(record.estado) === 0 ? 'selected' : ''}>Inactivo</option>
            </select>
            <small class="text-muted">Estado actual: ${record.estado} (${parseInt(record.estado) === 1 ? 'Activo' : 'Inactivo'})</small>
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
    
    // Event listener para el menú de ordenamiento
    document.querySelectorAll('.dropdown-item[data-sort]').forEach(item => {
        item.addEventListener('click', handleSortClick);
    });
    
    // Debug: Agregar event listener al formulario de edición
    document.addEventListener('submit', function(e) {
        if (e.target.closest('#editModal')) {
            e.preventDefault(); // Prevenir envío automático
            console.log('=== FORMULARIO DE EDICIÓN ENVIADO ===');
            
            const form = e.target;
            const formData = new FormData(form);
            
            // Debug específico para el estado
            const estadoSelect = form.querySelector('select[name="estado"]');
            if (estadoSelect) {
                console.log('ESTADO SELECCIONADO:', estadoSelect.value);
                console.log('TIPO DE ESTADO:', typeof estadoSelect.value);
                console.log('¿ES 0?:', estadoSelect.value === '0');
                console.log('¿ES 1?:', estadoSelect.value === '1');
            } else {
                console.log('ERROR: No se encontró el select de estado');
            }
            
            // Verificar todos los campos
            console.log('CAMPOS DEL FORMULARIO:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            
            // Enviar formulario manualmente
            console.log('Enviando formulario...');
            form.submit();
        }
    });
    
    // Debug adicional: Verificar si el modal se abre correctamente
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, verificando modal de edición');
        const editModal = document.getElementById('editModal');
        if (editModal) {
            console.log('Modal de edición encontrado');
            const form = editModal.querySelector('form');
            if (form) {
                console.log('Formulario encontrado en el modal');
                
                // Event listener específico para el botón de guardar
                const btnGuardar = form.querySelector('#btnGuardarCambios');
                if (btnGuardar) {
                    console.log('Botón de guardar encontrado');
                    btnGuardar.addEventListener('click', function(e) {
                        console.log('Botón de guardar clickeado');
                        console.log('Formulario válido:', form.checkValidity());
                        
                        // Verificar datos antes de enviar
                        const estadoSelect = form.querySelector('select[name="estado"]');
                        if (estadoSelect) {
                            console.log('Estado antes de enviar:', estadoSelect.value);
                        }
                    });
                } else {
                    console.log('ERROR: No se encontró botón de guardar');
                }
            } else {
                console.log('ERROR: No se encontró formulario en el modal');
            }
        } else {
            console.log('ERROR: No se encontró modal de edición');
        }
    });
    
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