<?php
session_start();
$rolUsuario = isset($_SESSION['user']['rol']) ? strtoupper($_SESSION['user']['rol']) : '';
if ($rolUsuario === 'VENDEDOR') {
    header('Location: ../index.php');
    exit;
}
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

// --- Obtener sucursal seleccionada de la sesión o usar la primera por defecto ---
if (!isset($_SESSION['sucursal_seleccionada'])) {
    // Obtener sucursales para establecer la primera como predeterminada
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/sucursales',
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
    
    // Filtrar sucursales de la empresa actual
    $sucursalesEmpresa = array_filter($sucursalesCompletas, function($sucursal) use ($empId) {
        return isset($sucursal['empresa']['id']) && 
               $sucursal['empresa']['id'] == $empId;
    });
    
    // Establecer la primera sucursal como predeterminada
    if (!empty($sucursalesEmpresa)) {
        $_SESSION['sucursal_seleccionada'] = reset($sucursalesEmpresa)['id'];
    }
}

// --- Manejar cambio de sucursal ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cambiar_sucursal') {
    $_SESSION['sucursal_seleccionada'] = intval($_POST['sucursal_id']);
    header('Location: caja.php');
    exit;
}

$sucursalSeleccionada = $_SESSION['sucursal_seleccionada'] ?? null;

// --- Llamada al endpoint para obtener todas las cajas ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/caja',
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

$cajasCompletas = json_decode($response, true);
if (!is_array($cajasCompletas)) $cajasCompletas = [];

// --- Filtrado para mostrar solo las cajas de la empresa actual ---
$cajasEmpresa = array_filter($cajasCompletas, function($caja) use ($empId) {
    return isset($caja['sucursalId']['empresa']['id']) && 
           $caja['sucursalId']['empresa']['id'] == $empId;
});

// --- Filtrado adicional por sucursal seleccionada ---
$cajasSucursal = $cajasEmpresa;
if ($sucursalSeleccionada) {
    $cajasSucursal = array_filter($cajasEmpresa, function($caja) use ($sucursalSeleccionada) {
        return isset($caja['sucursalId']['id']) && 
               $caja['sucursalId']['id'] == $sucursalSeleccionada;
    });
}

// --- Ordenar cajas: primero las abiertas (más recientes primero), luego las cerradas ---
usort($cajasSucursal, function($a, $b) {
    // Si ambas están abiertas o ambas cerradas, ordenar por ID descendente
    if ($a['estado'] == $b['estado']) {
        return $b['id'] - $a['id'];
    }
    // Si una está abierta y otra cerrada, la abierta va primero
    return $b['estado'] - $a['estado'];
});

// --- Buscar la última caja abierta del usuario actual en la sucursal seleccionada ---
$cajasAbiertas = array_filter($cajasSucursal, fn($c) => $c['estado'] == 1 && $c['usuarioId']['id'] == $userId);
$ultimaCajaAbierta = null;
if (!empty($cajasAbiertas)) {
    usort($cajasAbiertas, fn($a, $b) => $b['id'] - $a['id']);
    $ultimaCajaAbierta = $cajasAbiertas[0];
}

// --- Llamada al endpoint de usuarios para el formulario ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/usuarios',
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

$usuariosCompletos = json_decode($response, true);
if (!is_array($usuariosCompletos)) $usuariosCompletos = [];

// --- Filtrado para mostrar solo usuarios de la empresa actual ---
$usuariosEmpresa = array_filter($usuariosCompletos, function($usuario) use ($empId) {
    return isset($usuario['empresa']['id']) && 
           $usuario['empresa']['id'] == $empId;
});

// --- Llamada al endpoint de sucursales para el formulario ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/sucursales',
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

// --- Llamada al endpoint de movimientos de caja ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/movimientosCaja',
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

// --- Filtrado para mostrar solo movimientos de la empresa actual del día ---
$movimientosEmpresa = array_filter($movimientosCompletos, function($movimiento) use ($empId) {
    return isset($movimiento['caja']['sucursalId']['empresa']['id']) && 
           $movimiento['caja']['sucursalId']['empresa']['id'] == $empId &&
           date('Y-m-d') === date('Y-m-d', strtotime($movimiento['fecha']));
});

// --- Filtrado adicional de movimientos por sucursal seleccionada ---
$movimientosSucursal = $movimientosEmpresa;
if ($sucursalSeleccionada) {
    $movimientosSucursal = array_filter($movimientosEmpresa, function($movimiento) use ($sucursalSeleccionada) {
        return isset($movimiento['caja']['sucursalId']['id']) && 
               $movimiento['caja']['sucursalId']['id'] == $sucursalSeleccionada;
    });
}

// --- Lógica de apertura de caja POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'abrir_caja') {
    if ($ultimaCajaAbierta) {
        $errorMsg = 'Ya tienes una caja abierta en esta sucursal. Debes cerrarla antes de abrir una nueva.';
    } else if ($_POST['monto_inicial'] < 0) {
        $errorMsg = 'El monto inicial no puede ser negativo.';
    } else if (!$sucursalSeleccionada) {
        $errorMsg = 'Debe seleccionar una sucursal antes de abrir una caja.';
    } else {
        // Crear el payload
        $payload = [
            "sucursalId" => $sucursalSeleccionada,
            "usuarioId" => $userId,
            "apertura" => date('Y-m-d\TH:i:s'),
            "cierre" => null,
            "montoInicial" => floatval($_POST['monto_inicial']),
            "montoFinal" => null,
            "estado" => 1
        ];
        
        // Debug: Mostrar el payload que se va a enviar
        error_log("Payload a enviar: " . json_encode($payload));
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/caja',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
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
        error_log("Respuesta API caja: " . $response);
        error_log("HTTP Code: " . $httpCode);
        error_log("CURL Error: " . $curlError);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            // Obtener nombre de la sucursal para la auditoría
            $nombreSucursal = '';
            foreach ($sucursalesEmpresa as $sucursal) {
                if ($sucursal['id'] == $sucursalSeleccionada) {
                    $nombreSucursal = $sucursal['nombre'];
                    break;
                }
            }
            
            // Auditoría
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/auditoria',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "usuario": {"id":'.$userId.'},
                    "empresa": {"id":'.$empId.'},
                    "evento": "APERTURA DE CAJA",
                    "descripcion": "Se abrió caja en sucursal '.$nombreSucursal.' con monto inicial: S/ '.$_POST['monto_inicial'].'",
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
            $successMsg = 'Caja abierta correctamente en sucursal '.$nombreSucursal.'.';
            header('Location: caja.php?success=1');
            exit;
        } else {
            $errorMsg = 'Error al crear la caja. Código HTTP: ' . $httpCode . '. Respuesta: ' . $response;
        }
    }
}

// --- Lógica de cierre de caja POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cerrar_caja') {
    if (!$ultimaCajaAbierta || $_POST['caja_id'] != $ultimaCajaAbierta['id']) {
        $errorMsg = 'Solo puedes cerrar la última caja abierta.';
    } else if ($_POST['monto_final'] < 0) {
        $errorMsg = 'El monto final no puede ser negativo.';
    } else {
        // Crear el payload para actualizar la caja
        $payload = [
            "id" => intval($_POST['caja_id']),
            "sucursalId" => $ultimaCajaAbierta['sucursalId']['id'],
            "usuarioId" => $userId,
            "apertura" => $_POST['apertura'],
            "cierre" => date('Y-m-d\TH:i:s'),
            "montoInicial" => floatval($_POST['monto_inicial']),
            "montoFinal" => floatval($_POST['monto_final']),
            "estado" => 0
        ];
        
        // Debug: Mostrar el payload que se va a enviar
        error_log("Payload para cerrar caja: " . json_encode($payload));
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/caja',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($payload),
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
        error_log("Respuesta API cerrar caja: " . $response);
        error_log("HTTP Code: " . $httpCode);
        error_log("CURL Error: " . $curlError);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            // Auditoría
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/auditoria',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "usuario": {"id":'.$userId.'},
                    "empresa": {"id":'.$empId.'},
                    "evento": "CIERRE DE CAJA",
                    "descripcion": "Se cerró caja ID: '.$_POST['caja_id'].' en sucursal '.$ultimaCajaAbierta['sucursalId']['nombre'].' - Monto inicial: S/ '.$_POST['monto_inicial'].' - Monto final: S/ '.$_POST['monto_final'].'",
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
            $successMsg = 'Caja cerrada correctamente.';
            header('Location: caja.php?success=2');
            exit;
        } else {
            $errorMsg = 'Error al cerrar la caja. Código HTTP: ' . $httpCode . '. Respuesta: ' . $response;
        }
    }
}

// --- Lógica de edición POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && !isset($_POST['action'])) {
    // Crear el payload para actualizar la caja
    $payload = [
        "id" => intval($_POST['id']),
        "sucursalId" => intval($_POST['sucursal_id']),
        "usuarioId" => intval($_POST['usuario_id']),
        "apertura" => $_POST['apertura'],
        "cierre" => $_POST['cierre'] ? $_POST['cierre'] : null,
        "montoInicial" => floatval($_POST['monto_inicial']),
        "montoFinal" => $_POST['monto_final'] ? floatval($_POST['monto_final']) : null,
        "estado" => intval($_POST['estado'])
    ];
    
    // Debug: Mostrar el payload que se va a enviar
    error_log("Payload para editar caja: " . json_encode($payload));
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/caja',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode($payload),
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
    error_log("Respuesta API editar caja: " . $response);
    error_log("HTTP Code: " . $httpCode);
    error_log("CURL Error: " . $curlError);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        // --- Registrar en Auditoría ---
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1644/api/ropas/auditoria',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "usuario": {"id":'.$userId.'},
                "empresa": {"id":'.$empId.'},
                "evento": "EDICIÓN DE CAJA",
                "descripcion": "Se editó caja ID: '.$_POST['id'].' - Usuario: '.$_POST['usuario_id'].' - Monto inicial: '.$_POST['monto_inicial'].' - Estado: '.$_POST['estado'].'",
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
        
        header("Location: caja.php?success=3");
        exit;
    } else {
        $errorMsg = 'Error al editar la caja. Código HTTP: ' . $httpCode . '. Respuesta: ' . $response;
    }
}

// --- Cálculo de estadísticas ---
$total_cajas = count($cajasSucursal);
$cajas_abiertas = count(array_filter($cajasSucursal, fn($c) => $c['estado'] == 1));
$cajas_hoy = count(array_filter($cajasSucursal, fn($c) => date('Y-m-d') === date('Y-m-d', strtotime($c['apertura']))));

// Calcular total neto de movimientos (ingresos - egresos)
$total_ingresos = 0;
$total_egresos = 0;

foreach ($movimientosSucursal as $movimiento) {
    if ($movimiento['monto'] > 0) {
        $total_ingresos += $movimiento['monto'];
    } else {
        $total_egresos += abs($movimiento['monto']);
    }
}

$total_movimientos = $total_ingresos - $total_egresos; // Total neto

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

require_once 'config_colors.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Gestión de Caja | <?= $empName ?></title>
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
    .metric-card h5, .metric-card .display-4 {
      color: <?= $brandColor ?> !important;
    }
    /* Títulos de la modal */
    .modal-header.bg-light.text-success, .modal-header.bg-light.text-success h5, 
    .modal-header.bg-light.text-warning, .modal-header.bg-light.text-warning h5,
    .modal-title, #abrirCajaModalLabel, #cerrarCajaModalLabel, #editModalLabel, #viewModalLabel {
      color: var(--brand-color) !important;
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
          <i class="bi bi-cash-stack me-2"></i>Gestión de Caja
        </h1>
        <div>
          <button class="btn btn-primary d-flex align-items-center me-2 <?= $ultimaCajaAbierta ? 'disabled' : '' ?>" 
                  data-bs-toggle="<?= $ultimaCajaAbierta ? '' : 'modal' ?>" 
                  data-bs-target="<?= $ultimaCajaAbierta ? '' : '#abrirCajaModal' ?>"
                  <?= $ultimaCajaAbierta ? 'disabled' : '' ?>
                  title="<?= $ultimaCajaAbierta ? 'Ya hay una caja abierta. Debe cerrarla antes de abrir una nueva.' : 'Abrir nueva caja' ?>">
            <i class="bi bi-cash-coin me-1"></i> Abrir Caja
          </button>
          <p> </p>


          <button id="exportBtn" class="btn btn-success d-flex align-items-center">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel
          </button>
        </div>
      </div>

      

      <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= $errorMsg ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php 
          switch($_GET['success']) {
            case '1':
              echo 'Caja abierta correctamente.';
              break;
            case '2':
              echo 'Caja cerrada correctamente.';
              break;
            case '3':
              echo 'Caja editada correctamente.';
              break;
            default:
              echo 'Operación realizada correctamente.';
          }
          ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Tarjetas de Estadísticas -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-cash-stack text-success fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($total_cajas) ?></h4>
              <p class="text-muted mb-0">Total de Cajas</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-unlock-fill text-warning fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($cajas_abiertas) ?></h4>
              <p class="text-muted mb-0">Cajas Abiertas</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-calendar-check text-info fs-1"></i>
              <h4 class="mt-2 fw-bold"><?= number_format($cajas_hoy) ?></h4>
              <p class="text-muted mb-0">Cajas Hoy</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card stats-card border-0 shadow-sm">
            <div class="card-body text-center">
              <i class="bi bi-currency-dollar text-primary fs-1"></i>
              <h4 class="mt-2 fw-bold <?= $total_movimientos >= 0 ? 'text-success' : 'text-danger' ?>">
                S/ <?= number_format($total_movimientos, 2) ?>
              </h4>
              <p class="text-muted mb-0">Total Neto Hoy</p>
              <?php if ($nombreSucursalSeleccionada): ?>
                <small class="text-muted"><?= $nombreSucursalSeleccionada ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Cajas -->
      <div class="card shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-success-emphasis">
            Historial de Cajas
            <?php if ($nombreSucursalSeleccionada): ?>
              <small class="text-muted">- <?= $nombreSucursalSeleccionada ?></small>
            <?php endif; ?>
          </h5>
          <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por usuario o fecha...">
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="text-center">
                  <th>#</th>
                  <th class="text-start">Usuario</th>
                  <th>Sucursal</th>
                  <th>Monto Inicial</th>
                  <th>Monto Final</th>
                  <th>Apertura</th>
                  <th>Cierre</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="cajas-table-body">
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

  <!-- Modal: Abrir Caja -->
  <div class="modal fade" id="abrirCajaModal" tabindex="-1" aria-labelledby="abrirCajaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <input type="hidden" name="action" value="abrir_caja">
          <div class="modal-header bg-light text-success border-bottom-0">
            <h5 class="modal-title" id="abrirCajaModalLabel"><i class="bi bi-cash-coin me-2"></i>Abrir Caja</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-bold">Monto Inicial (S/)</label>
              <input type="number" name="monto_inicial" class="form-control" step="0.01" min="0" required>
              <div class="form-text">Ingrese el monto inicial con el que abrirá la caja</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success">Abrir Caja</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Cerrar Caja -->
  <div class="modal fade" id="cerrarCajaModal" tabindex="-1" aria-labelledby="cerrarCajaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <input type="hidden" name="action" value="cerrar_caja">
          <div class="modal-header bg-light text-warning border-bottom-0">
            <h5 class="modal-title" id="cerrarCajaModalLabel"><i class="bi bi-lock-fill me-2"></i>Cerrar Caja</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="cerrarCajaModalBody">
            <!-- El contenido se inyectará aquí con JS -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning">Cerrar Caja</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Editar Caja -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-light text-success border-bottom-0">
            <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-square me-2"></i>Editar Caja</h5>
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
        <div class="modal-header bg-light text-success border-bottom-0">
          <h5 class="modal-title" id="viewModalLabel"><i class="bi bi-cash-stack me-2"></i>Detalle de Caja</h5>
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
    const cajasData = <?php echo json_encode(array_values($cajasSucursal)); ?>;
    const usuariosData = <?php echo json_encode(array_values($usuariosEmpresa)); ?>;
    const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    const cerrarCajaModal = new bootstrap.Modal(document.getElementById('cerrarCajaModal'));

    // --- Lógica de Búsqueda y Paginación ---
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('cajas-table-body');
    const paginationContainer = document.getElementById('pagination-container');
    const rowsPerPage = 15;
    let currentPage = 1;
    let filteredData = [...cajasData];

    function renderTable() {
      tableBody.innerHTML = '';
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const paginatedData = filteredData.slice(start, end);

      if (paginatedData.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="9" class="text-center text-muted py-4">
              <i class="bi bi-search fs-2"></i>
              <p class="mt-2 mb-0">No se encontraron resultados.</p>
            </td>
          </tr>`;
        return;
      }

      paginatedData.forEach((row, index) => {
        const globalIndex = start + index + 1;
        const estadoBadge = row.estado == 1 
          ? '<span class="badge bg-success">Abierta</span>'
          : '<span class="badge bg-secondary">Cerrada</span>';

        const rowHtml = `
          <tr>
            <td class="text-center fw-bold">${globalIndex}</td>
            <td>${row.usuarioId.nombre}</td>
            <td class="text-center">
              <span class="badge bg-info-subtle text-info-emphasis">${row.sucursalId.nombre}</span>
            </td>
            <td class="text-center">
              <span class="badge bg-primary-subtle text-primary-emphasis">S/ ${parseFloat(row.montoInicial).toFixed(2)}</span>
            </td>
            <td class="text-center">
              ${row.montoFinal ? `<span class="badge bg-info-subtle text-info-emphasis">S/ ${parseFloat(row.montoFinal).toFixed(2)}</span>` : '<span class="text-muted">-</span>'}
            </td>
            <td class="text-center">${new Date(row.apertura).toLocaleString()}</td>
            <td class="text-center">${row.cierre ? new Date(row.cierre).toLocaleString() : '<span class="text-muted">-</span>'}</td>
            <td class="text-center">${estadoBadge}</td>
            <td class="text-center">
              ${row.estado == 1 ? `
                <button class="btn btn-outline-warning btn-sm" title="Cerrar caja" onclick="openCerrarModal(${row.id})">
                  <i class="bi bi-lock"></i>
                </button>
              ` : `
                <?php if ($rolUsuario === 'ADMIN'): ?>
                  <button class="btn btn-outline-primary btn-sm" title="Editar" onclick="openEditModal(${row.id})">
                    <i class="bi bi-pencil"></i>
                  </button>
                <?php endif; ?>
              `}
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
      filteredData = cajasData.filter(row => {
        return row.usuarioId.nombre.toLowerCase().includes(searchTerm) ||
               row.sucursalId.nombre.toLowerCase().includes(searchTerm) ||
               new Date(row.apertura).toLocaleDateString().includes(searchTerm);
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
      return cajasData.find(c => c.id == id);
    }

    function openCerrarModal(id) {
      const record = findRecordById(id);
      if (!record) return;

      let modalBody = `
        <input type="hidden" name="caja_id" value="${record.id}">
        <input type="hidden" name="apertura" value="${record.apertura}">
        <input type="hidden" name="monto_inicial" value="${record.montoInicial}">
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>
          <strong>Caja ID:</strong> ${record.id}<br>
          <strong>Sucursal:</strong> ${record.sucursalId.nombre}<br>
          <strong>Abierta por:</strong> ${record.usuarioId.nombre}<br>
          <strong>Monto inicial:</strong> S/ ${parseFloat(record.montoInicial).toFixed(2)}
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Monto Final (S/)</label>
          <input type="number" name="monto_final" class="form-control" step="0.01" min="0" required>
          <div class="form-text">Ingrese el monto final con el que cerrará la caja</div>
        </div>
      `;
      
      document.getElementById('cerrarCajaModalBody').innerHTML = modalBody;
      cerrarCajaModal.show();
    }

    function openEditModal(id) {
      const record = findRecordById(id);
      if (!record) return;

      let modalBody = `
        <input type="hidden" name="id" value="${record.id}">
        <div class="row">
          <div class="col-md-6">
            <label class="form-label fw-bold">Usuario</label>
            <select name="usuario_id" class="form-select" required>
              <option value="">Seleccionar Usuario</option>
              ${usuariosData.map(usuario => `
                <option value="${usuario.id}" ${record.usuarioId.id == usuario.id ? 'selected' : ''}>
                  ${usuario.nombre}
                </option>
              `).join('')}
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Estado</label>
            <select name="estado" class="form-select" required>
              <option value="1" ${record.estado == 1 ? 'selected' : ''}>Abierta</option>
              <option value="0" ${record.estado == 0 ? 'selected' : ''}>Cerrada</option>
            </select>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Fecha de Apertura</label>
            <input type="datetime-local" name="apertura" class="form-control" value="${record.apertura.replace('T', ' ').substring(0, 16)}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Fecha de Cierre</label>
            <input type="datetime-local" name="cierre" class="form-control" value="${record.cierre ? record.cierre.replace('T', ' ').substring(0, 16) : ''}">
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Monto Inicial (S/)</label>
            <input type="number" name="monto_inicial" class="form-control" step="0.01" min="0" value="${record.montoInicial}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Monto Final (S/)</label>
            <input type="number" name="monto_final" class="form-control" step="0.01" min="0" value="${record.montoFinal || ''}">
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
            <h5>Información de la Caja</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><strong>ID:</strong> ${record.id}</li>
              <li class="list-group-item"><strong>Usuario:</strong> ${record.usuarioId.nombre}</li>
              <li class="list-group-item"><strong>Sucursal:</strong> ${record.sucursalId.nombre}</li>
              <li class="list-group-item"><strong>Estado:</strong> ${record.estado == 1 ? '<span class="badge bg-success">Abierta</span>' : '<span class="badge bg-secondary">Cerrada</span>'}</li>
              <li class="list-group-item"><strong>Monto Inicial:</strong> S/ ${parseFloat(record.montoInicial).toFixed(2)}</li>
              <li class="list-group-item"><strong>Monto Final:</strong> ${record.montoFinal ? 'S/ ' + parseFloat(record.montoFinal).toFixed(2) : 'No cerrada'}</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h5>Horarios</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item"><strong>Apertura:</strong> ${new Date(record.apertura).toLocaleString()}</li>
              <li class="list-group-item"><strong>Cierre:</strong> ${record.cierre ? new Date(record.cierre).toLocaleString() : 'No cerrada'}</li>
              <li class="list-group-item"><strong>Duración:</strong> ${record.cierre ? getDuration(record.apertura, record.cierre) : 'En curso'}</li>
            </ul>
          </div>
        </div>
        ${record.cierre ? `
        <div class="row mt-3">
          <div class="col-12">
            <div class="alert alert-info">
              <h6>Resumen de Movimientos</h6>
              <p><strong>Diferencia:</strong> S/ ${(parseFloat(record.montoFinal) - parseFloat(record.montoInicial)).toFixed(2)}</p>
            </div>
          </div>
        </div>
        ` : ''}
      `;
      document.getElementById('viewModalBody').innerHTML = modalBody;
      viewModal.show();
    }

    function getDuration(start, end) {
      const startDate = new Date(start);
      const endDate = new Date(end);
      const diffMs = endDate - startDate;
      const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
      const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
      return `${diffHrs}h ${diffMins}m`;
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
        "ID", "Usuario", "Sucursal", "Monto Inicial", "Monto Final", "Apertura", "Cierre", "Estado"
      ];

      let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n";

      cajasData.forEach(record => {
        const usuarioLimpio = `"${record.usuarioId.nombre}"`;
        const sucursalLimpia = `"${record.sucursalId.nombre}"`;
        const estado = record.estado == 1 ? "Abierta" : "Cerrada";
        
        const row = [
          record.id,
          usuarioLimpio,
          sucursalLimpia,
          parseFloat(record.montoInicial).toFixed(2),
          record.montoFinal ? parseFloat(record.montoFinal).toFixed(2) : "",
          new Date(record.apertura).toLocaleString(),
          record.cierre ? new Date(record.cierre).toLocaleString() : "",
          estado
        ].join(",");
        
        csvContent += row + "\n";
      });

      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "cajas.csv");
      document.body.appendChild(link);
      
      link.click();
      
      document.body.removeChild(link);
    }
  </script>

</body>
</html> 