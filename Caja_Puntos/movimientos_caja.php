<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

// Configurar zona horaria de Perú
date_default_timezone_set('America/Lima');

$user = $_SESSION['user'];
$empId = $user['empresa']['id'];
$userId = $user['id'];
$empName = htmlspecialchars($user['empresa']['nombre'], ENT_QUOTES);

// --- Variables para mensajes de error ---
$errorMsg = '';
$successMsg = '';

// --- Manejar cambio de sucursal ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cambiar_sucursal') {
    $_SESSION['sucursal_seleccionada'] = intval($_POST['sucursal_id']);
    header('Location: movimientos_caja.php');
    exit;
}

// --- Obtener sucursal seleccionada de la sesión ---
$sucursalSeleccionada = $_SESSION['sucursal_seleccionada'] ?? null;

// --- Llamada al endpoint de sucursales para obtener el nombre ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/sucursales',
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

$sucursalesCompletas = json_decode($response, true);
if (!is_array($sucursalesCompletas)) $sucursalesCompletas = [];

// --- Filtrado para mostrar solo sucursales de la empresa actual ---
$sucursalesEmpresa = array_filter($sucursalesCompletas, function($sucursal) use ($empId) {
    return isset($sucursal['empresa']['id']) && 
           $sucursal['empresa']['id'] == $empId;
});

// --- Obtener nombre de la sucursal seleccionada ---
$nombreSucursalSeleccionada = '';
if ($sucursalSeleccionada) {
    foreach ($sucursalesEmpresa as $sucursal) {
        if ($sucursal['id'] == $sucursalSeleccionada) {
            $nombreSucursalSeleccionada = $sucursal['nombre'];
            break;
        }
    }
}

// --- Llamada al endpoint de movimientos de caja ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/movimientosCaja',
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

$movimientosCompletos = json_decode($response, true);
if (!is_array($movimientosCompletos)) $movimientosCompletos = [];

// --- Filtrado para mostrar solo movimientos de la empresa actual ---
$movimientosEmpresa = array_filter($movimientosCompletos, function($movimiento) use ($empId) {
    return isset($movimiento['caja']['sucursalId']['empresa']['id']) && 
           $movimiento['caja']['sucursalId']['empresa']['id'] == $empId;
});

// --- Filtrado adicional por sucursal seleccionada ---
$movimientosSucursal = $movimientosEmpresa;
if ($sucursalSeleccionada) {
    $movimientosSucursal = array_filter($movimientosEmpresa, function($movimiento) use ($sucursalSeleccionada) {
        return isset($movimiento['caja']['sucursalId']['id']) && 
               $movimiento['caja']['sucursalId']['id'] == $sucursalSeleccionada;
    });
}

// --- Cálculo de estadísticas ---
$total_movimientos = count($movimientosSucursal);

// Calcular movimientos por tipo
$movimientos_ingresos = 0;
$movimientos_egresos = 0;
$total_ingresos = 0;
$total_egresos = 0;

foreach ($movimientosSucursal as $movimiento) {
    if ($movimiento['monto'] > 0) {
        $movimientos_ingresos++;
        $total_ingresos += $movimiento['monto'];
    } else {
        $movimientos_egresos++;
        $total_egresos += abs($movimiento['monto']);
    }
}

// Calcular total neto (ingresos - egresos)
$total_neto = $total_ingresos - $total_egresos;

// Filtrar movimientos del día
$movimientosHoy = array_filter($movimientosSucursal, function($movimiento) {
    return date('Y-m-d') === date('Y-m-d', strtotime($movimiento['fecha']));
});

$movimientos_hoy = count($movimientosHoy);

require_once 'config_colors.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Movimientos de Caja | <?= $empName ?></title>
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

  <div class="d-flex">
    <main class="content flex-grow-1 p-4">

      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 fw-bold page-title">
          <i class="bi bi-arrow-left-right me-2"></i>Movimientos de Caja
        </h1>
        <div>
          <button id="exportBtn" class="btn btn-success d-flex align-items-center">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel
          </button>
        </div>
      </div>

      <!-- Tarjetas de Estadísticas -->
      <div class="row mb-4">
        <div class="col-md-2">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-arrow-left-right text-primary fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($total_movimientos) ?></h4>
              <p class="text-muted mb-0">Total Movimientos</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-calendar-check text-info fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($movimientos_hoy) ?></h4>
              <p class="text-muted mb-0">Movimientos Hoy</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-arrow-up-circle text-success fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($movimientos_ingresos) ?></h4>
              <p class="text-muted mb-0">Total Ingresos</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-arrow-down-circle text-danger fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($movimientos_egresos) ?></h4>
              <p class="text-muted mb-0">Total Egresos</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-currency-dollar text-success fs-1"></i>
              <h4 class="mt-2 fw-bold">S/ <?= number_format($total_ingresos, 2) ?></h4>
              <p class="text-muted mb-0">Monto Ingresos</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-currency-dollar text-danger fs-1"></i>
              <h4 class="mt-2 fw-bold">S/ <?= number_format($total_egresos, 2) ?></h4>
              <p class="text-muted mb-0">Monto Egresos</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Movimientos de Caja -->
      <div class="card shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-primary-emphasis">
            Historial de Movimientos
            <?php if ($nombreSucursalSeleccionada): ?>
              <small class="text-muted">- <?= $nombreSucursalSeleccionada ?></small>
            <?php endif; ?>
          </h5>
          <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por descripción o caja...">
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="text-center">
                  <th>#</th>
                  <th class="text-start">Caja</th>
                  <th class="text-start">Sucursal</th>
                  <th class="text-start">Descripción</th>
                  <th>Tipo</th>
                  <th>Monto</th>
                  <th>Fecha</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody id="movimientos-table-body">
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

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  
  <script>
    // Pasamos los datos de PHP a JavaScript de forma segura
    const movimientosData = <?php echo json_encode(array_values($movimientosSucursal)); ?>;
    
    // Configuración de paginación
    const itemsPerPage = 10;
    let currentPage = 1;
    let filteredData = [...movimientosData];

    // Función para renderizar la tabla
    function renderTable(data) {
      const tbody = document.getElementById('movimientos-table-body');
      const startIndex = (currentPage - 1) * itemsPerPage;
      const endIndex = startIndex + itemsPerPage;
      const pageData = data.slice(startIndex, endIndex);

      tbody.innerHTML = pageData.map((movimiento, index) => `
        <tr class="text-center">
          <td>${startIndex + index + 1}</td>
          <td class="text-start">
            <div class="fw-bold">Caja #${movimiento.caja.id}</div>
          </td>
          <td class="text-start">
            <span class="badge bg-info-subtle text-info-emphasis">${movimiento.caja.sucursalId.nombre}</span>
          </td>
          <td class="text-start">${movimiento.descripcion}</td>
          <td>
            <span class="badge ${movimiento.tipo === 'APERTURA' ? 'bg-success' : 'bg-warning'}">
              ${movimiento.tipo}
            </span>
          </td>
          <td>
            <span class="fw-bold ${movimiento.monto >= 0 ? 'text-success' : 'text-danger'}">
              S/ ${Math.abs(movimiento.monto).toFixed(2)}
            </span>
          </td>
          <td>${new Date(movimiento.fecha).toLocaleString('es-PE')}</td>
          <td>
            <span class="badge ${movimiento.estado === 1 ? 'bg-success' : 'bg-secondary'}">
              ${movimiento.estado === 1 ? 'Activo' : 'Inactivo'}
            </span>
          </td>
        </tr>
      `).join('');

      renderPagination(data.length);
    }

    // Función para renderizar paginación
    function renderPagination(totalItems) {
      const totalPages = Math.ceil(totalItems / itemsPerPage);
      const paginationContainer = document.getElementById('pagination-container');
      
      if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
      }

      let paginationHTML = '<ul class="pagination mb-0">';
      
      // Botón anterior
      paginationHTML += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Anterior</a>
        </li>
      `;

      // Números de página
      for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
          paginationHTML += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
              <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
          `;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
          paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
      }

      // Botón siguiente
      paginationHTML += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Siguiente</a>
        </li>
      `;

      paginationHTML += '</ul>';
      paginationContainer.innerHTML = paginationHTML;
    }

    // Función para cambiar página
    function changePage(page) {
      currentPage = page;
      renderTable(filteredData);
    }

    // Función de búsqueda
    document.getElementById('searchInput').addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      filteredData = movimientosData.filter(movimiento => 
        movimiento.descripcion.toLowerCase().includes(searchTerm) ||
        movimiento.caja.sucursalId.nombre.toLowerCase().includes(searchTerm) ||
        movimiento.caja.id.toString().includes(searchTerm) ||
        movimiento.tipo.toLowerCase().includes(searchTerm)
      );
      currentPage = 1;
      renderTable(filteredData);
    });

    // Función para exportar a Excel
    document.getElementById('exportBtn').addEventListener('click', function() {
      const ws = XLSX.utils.json_to_sheet(filteredData.map(movimiento => ({
        'ID': movimiento.id,
        'Caja': movimiento.caja.id,
        'Sucursal': movimiento.caja.sucursalId.nombre,
        'Descripción': movimiento.descripcion,
        'Tipo': movimiento.tipo,
        'Monto': movimiento.monto,
        'Fecha': new Date(movimiento.fecha).toLocaleString('es-PE'),
        'Estado': movimiento.estado === 1 ? 'Activo' : 'Inactivo'
      })));

      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Movimientos de Caja');
      
      const fileName = `movimientos_caja_${new Date().toISOString().split('T')[0]}.xlsx`;
      XLSX.writeFile(wb, fileName);
    });

    // Inicializar tabla
    renderTable(filteredData);
  </script>
</body>
</html> 