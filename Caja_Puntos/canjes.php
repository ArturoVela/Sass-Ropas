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
    // Actualizar datos del canje
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/Canjes',
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
    // Crear nuevo canje - NO enviar el campo id
    
    // --- VALIDACIONES ANTES DE CREAR EL CANJE ---
    $error_messages = [];
    
    // 1. Verificar que la recompensa esté activa
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/recompensas',
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
    $recompensasResponse = curl_exec($curl);
    curl_close($curl);
    
    $recompensas = json_decode($recompensasResponse, true);
    $recompensaSeleccionada = null;
    foreach ($recompensas as $recompensa) {
        if ($recompensa['id'] == $_POST['recompensa_id']) {
            $recompensaSeleccionada = $recompensa;
            break;
        }
    }
    
    if (!$recompensaSeleccionada) {
        $error_messages[] = "La recompensa seleccionada no existe.";
    } elseif ($recompensaSeleccionada['estado'] != 1) {
        $error_messages[] = "La recompensa seleccionada está desactivada y no puede ser canjeada.";
    } elseif ($recompensaSeleccionada['stock'] <= 0) {
        $error_messages[] = "La recompensa seleccionada no tiene stock disponible.";
    }
    
    // 2. Verificar que el cliente tenga suficientes puntos
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
    $puntosResponse = curl_exec($curl);
    curl_close($curl);
    
    $puntosClientes = json_decode($puntosResponse, true);
    $clientePuntos = null;
    foreach ($puntosClientes as $puntoCliente) {
        if ($puntoCliente['clienteId']['id'] == $_POST['cliente_id']) {
            $clientePuntos = $puntoCliente;
            break;
        }
    }
    
    if (!$clientePuntos) {
        $error_messages[] = "El cliente seleccionado no existe en el sistema de puntos.";
    } else {
        $puntosDisponibles = $clientePuntos['puntos_acumulados'] - $clientePuntos['puntos_utilizados'];
        $puntosRecompensa = $recompensaSeleccionada ? $recompensaSeleccionada['puntos_requeridos'] : 0;
        
        if ($puntosDisponibles < $puntosRecompensa) {
            $error_messages[] = "El cliente no tiene suficientes puntos. Puntos disponibles: $puntosDisponibles, Puntos requeridos: $puntosRecompensa";
        }
    }
    
    // Si hay errores, redirigir con mensaje de error
    if (!empty($error_messages)) {
        $_SESSION['error_messages'] = $error_messages;
        header("Location: canjes.php");
        exit;
    }
    
    // --- SI NO HAY ERRORES, PROCEDER CON LA CREACIÓN DEL CANJE ---
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/Canjes',
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
    
    $puntosRecompensa = $recompensaSeleccionada ? $recompensaSeleccionada['puntos_requeridos'] : 0;
    
    if ($clientePuntos) {
        // --- Actualizar puntos del cliente (restar de acumulados y sumar a utilizados) ---
        $nuevosPuntosAcumulados = $clientePuntos['puntos_acumulados'] - $puntosRecompensa;
        $nuevosPuntosUtilizados = $clientePuntos['puntos_utilizados'] + $puntosRecompensa;
        
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
                "id": '.$clientePuntos['id'].',
                "clienteId": '.$_POST['cliente_id'].',
                "puntos_acumulados": '.$nuevosPuntosAcumulados.',
                "puntos_utilizados": '.$nuevosPuntosUtilizados.',
                "ultima_actualizacion": "'.date('Y-m-d\TH:i:s').'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
            ),
        ));
        $responsePuntos = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        // Verificar si la actualización de puntos fue exitosa
        if ($httpCode !== 200) {
            $error_messages[] = "Error al actualizar los puntos del cliente. Código HTTP: $httpCode";
            $_SESSION['error_messages'] = $error_messages;
            header("Location: canjes.php");
            exit;
        }
    }
    
    // --- Crear registro en el historial de puntos ---
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/historialpuntos',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "clienteId": '.$_POST['cliente_id'].',
            "tipo": "canje",
            "puntos": '.$puntosRecompensa.',
            "descripcion": "Canje de recompensa: '.($recompensaSeleccionada ? $recompensaSeleccionada['nombre'] : 'Recompensa').'",
            "fecha": "'.date('Y-m-d\TH:i:s').'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
        ),
    ));
    $responseHistorial = curl_exec($curl);
    $httpCodeHistorial = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Verificar si la creación del historial fue exitosa
    if ($httpCodeHistorial !== 200) {
        $error_messages[] = "Error al crear el registro en el historial de puntos. Código HTTP: $httpCodeHistorial";
        $_SESSION['error_messages'] = $error_messages;
        header("Location: canjes.php");
        exit;
    }
    
    // --- Actualizar stock de la recompensa (restar 1) ---
    if ($recompensaSeleccionada) {
        $nuevoStock = $recompensaSeleccionada['stock'] - 1;
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/recompensas',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "id": '.$recompensaSeleccionada['id'].',
                "nombre": "'.$recompensaSeleccionada['nombre'].'",
                "descripcion": "'.$recompensaSeleccionada['descripcion'].'",
                "puntos_requeridos": '.$recompensaSeleccionada['puntos_requeridos'].',
                "stock": '.$nuevoStock.',
                "estado": '.$recompensaSeleccionada['estado'].'
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
            ),
        ));
        $responseStock = curl_exec($curl);
        $httpCodeStock = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        // Verificar si la actualización del stock fue exitosa
        if ($httpCodeStock !== 200) {
            $error_messages[] = "Error al actualizar el stock de la recompensa. Código HTTP: $httpCodeStock";
            $_SESSION['error_messages'] = $error_messages;
            header("Location: canjes.php");
            exit;
        }
    }
    
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
            "evento": "CREACIÓN DE CANJE",
            "descripcion": "Se creó nuevo canje - Cliente ID: '.$_POST['cliente_id'].' - Recompensa ID: '.$_POST['recompensa_id'].' - Puntos utilizados: '.$puntosRecompensa.'",
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
    
    // --- Mensaje de éxito ---
    $nombreCliente = '';
    $nombreRecompensa = '';
    
    // Obtener nombre del cliente
    foreach ($clientesEmpresa as $cliente) {
        if ($cliente['clienteId']['id'] == $_POST['cliente_id']) {
            $nombreCliente = $cliente['clienteId']['nombre'];
            break;
        }
    }
    
    // Obtener nombre de la recompensa
    if ($recompensaSeleccionada) {
        $nombreRecompensa = $recompensaSeleccionada['nombre'];
    }
    
    $_SESSION['success_message'] = "Canje exitoso: $nombreCliente canjeó '$nombreRecompensa' por $puntosRecompensa puntos. Puntos restantes: " . ($clientePuntos['puntos_acumulados'] - $clientePuntos['puntos_utilizados'] - $puntosRecompensa);
    
    header("Location: canjes.php");
    exit;
}

// --- Llamada al endpoint para obtener todos los canjes ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/canjes',
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

// --- Ordenar canjes por fecha (más recientes primero) ---
usort($canjesEmpresa, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

// --- Llamada al endpoint de clientes para el formulario ---
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
curl_close($curl);

$clientesCompletos = json_decode($response, true);
if (!is_array($clientesCompletos)) $clientesCompletos = [];
$clientesEmpresa = array_filter($clientesCompletos, fn($c) => isset($c['clienteId']['empresaId']['id']) && $c['clienteId']['empresaId']['id'] == $empId);

// --- Llamada al endpoint de recompensas para el formulario ---
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1655/api/ropas/recompensas',
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
      color: var(--brand-color) !important;
    }
    /* Título de la tabla */
    .table-title, .card-header.bg-white h5 {
      color: var(--brand-color) !important;
      font-weight: bold;
    }
    /* Botones de paginación */
    .pagination .page-link {
      color: var(--brand-color) !important;
      border-color: var(--brand-color) !important;
    }
    .pagination .page-item.active .page-link {
      background-color: var(--brand-color) !important;
      border-color: var(--brand-color) !important;
      color: #fff !important;
    }
    .pagination .page-link:focus, .pagination .page-link:hover {
      color: #fff !important;
      background-color: var(--brand-color) !important;
      border-color: var(--brand-color) !important;
    }
    /* Títulos de la modal */
    .modal-header.bg-light.text-danger, .modal-header.bg-light.text-danger h5, .modal-title, #createModalLabel, #editModalLabel {
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
        <h1 class="display-6 text-danger fw-bold page-title">
          <i class="bi bi-gift-fill me-2"></i>Canjes de Puntos
        </h1>
        <div>
          <button class="btn btn-primary d-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle-fill me-1"></i> Nuevo Canje
          </button>
          <p> </p>

          <button id="exportBtn" class="btn btn-success d-flex align-items-center">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel
          </button>
        </div>
      </div>

      <!-- Mensajes de Error -->
      <?php if (isset($_SESSION['error_messages']) && !empty($_SESSION['error_messages'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <h6 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error al crear el canje</h6>
          <ul class="mb-0">
            <?php foreach ($_SESSION['error_messages'] as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_messages']); ?>
      <?php endif; ?>

      <!-- Mensajes de Éxito -->
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <h6 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Canje exitoso</h6>
          <p class="mb-0"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>

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
          <h5 class="mb-0 text-danger-emphasis table-title">Listado de Canjes</h5>
          <div class="row w-100" style="max-width: 700px; margin-left: auto;">
            <div class="col-auto pe-0 me-2">
              <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="ordenarDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="background: var(--brand-color); border-color: var(--brand-color); color: #fff; min-width: 180px; height: 38px;">
                  <i class="bi bi-sort-alpha-down"></i> Ordenar por
                </button>
                <ul class="dropdown-menu" aria-labelledby="ordenarDropdown">
                  <li><h6 class="dropdown-header">Puntos Canjeados</h6></li>
                  <li><a class="dropdown-item ordenar-opcion" href="#" data-sort="puntos-desc">Mayor a menor</a></li>
                  <li><a class="dropdown-item ordenar-opcion" href="#" data-sort="puntos-asc">Menor a mayor</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><h6 class="dropdown-header">Cliente</h6></li>
                  <li><a class="dropdown-item ordenar-opcion" href="#" data-sort="cliente-az">A-Z</a></li>
                  <li><a class="dropdown-item ordenar-opcion" href="#" data-sort="cliente-za">Z-A</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><h6 class="dropdown-header">Recompensa</h6></li>
                  <li><a class="dropdown-item ordenar-opcion" href="#" data-sort="recompensa-az">A-Z</a></li>
                  <li><a class="dropdown-item ordenar-opcion" href="#" data-sort="recompensa-za">Z-A</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><h6 class="dropdown-header">Fecha</h6></li>
                  <li><a class="dropdown-item ordenar-opcion" href="#" data-sort="fecha-desc">Más reciente</a></li>
                  <li><a class="dropdown-item ordenar-opcion" href="#" data-sort="fecha-asc">Menos reciente</a></li>
                </ul>
              </div>
            </div>
            <div class="col-8 ps-0">
              <input type="text" id="searchInput" class="form-control" placeholder="Buscar por cliente o recompensa...">
            </div>
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
                <select name="cliente_id" id="clienteSelect" class="form-select" required onchange="filtrarRecompensas()">
                  <option value="">Seleccionar Cliente</option>
                  <?php foreach ($clientesEmpresa as $cliente): ?>
                    <option value="<?= $cliente['clienteId']['id'] ?>" data-puntos="<?= $cliente['puntos_acumulados'] ?>">
                      <?= htmlspecialchars($cliente['clienteId']['nombre']) ?> - <?= htmlspecialchars($cliente['clienteId']['numeroDocumento']) ?> (<?= $cliente['puntos_acumulados'] ?> pts)
                    </option>
                  <?php endforeach; ?>
                </select>
                <div id="puntosInfo" class="mt-2" style="display: none;">
                  <small class="text-muted">
                    <strong>Puntos acumulados:</strong> <span id="puntosAcumulados">0</span><br>
                    <strong>Puntos utilizados:</strong> <span id="puntosUtilizados">0</span><br>
                    <strong>Puntos disponibles:</strong> <span id="puntosDisponibles">0</span>
                  </small>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Recompensa</label>
                <select name="recompensa_id" id="recompensaSelect" class="form-select" required>
                  <option value="">Seleccionar Recompensa</option>
                  <?php foreach ($recompensasCompletas as $recompensa): ?>
                    <option value="<?= $recompensa['id'] ?>" data-puntos="<?= $recompensa['puntos_requeridos'] ?>" data-stock="<?= $recompensa['stock'] ?>" data-estado="<?= $recompensa['estado'] ?>">
                      <?= htmlspecialchars($recompensa['nombre']) ?> (<?= $recompensa['puntos_requeridos'] ?> pts) - Stock: <?= $recompensa['stock'] ?><?= $recompensa['estado'] == 0 ? ' - DESACTIVADA' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div id="recompensaInfo" class="mt-2" style="display: none;">
                  <small class="text-muted">
                    <strong>Puntos requeridos:</strong> <span id="puntosRequeridos">0</span><br>
                    <strong>Stock disponible:</strong> <span id="stockDisponible">0</span><br>
                    <strong>Estado:</strong> <span id="estadoRecompensa">Activa</span><br>
                    <strong>Puntos restantes después del canje:</strong> <span id="puntosRestantes" class="text-success fw-bold">0</span>
                  </small>
                </div>
                <div id="noRecompensasMsg" class="mt-2 alert alert-warning" style="display: none;">
                  <small>
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Este cliente no tiene suficientes puntos para canjear ninguna recompensa disponible.
                  </small>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" id="btnCrearCanje" class="btn btn-success" disabled>Crear Canje</button>
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
    
    // Inicializar modal de crear canje
    document.getElementById('createModal').addEventListener('show.bs.modal', function() {
      // Limpiar formulario
      document.getElementById('clienteSelect').value = '';
      document.getElementById('recompensaSelect').value = '';
      document.getElementById('puntosInfo').style.display = 'none';
      document.getElementById('recompensaInfo').style.display = 'none';
      document.getElementById('noRecompensasMsg').style.display = 'none';
      document.getElementById('btnCrearCanje').disabled = true;
      
      // Mostrar solo recompensas activas
      Array.from(document.getElementById('recompensaSelect').options).forEach(option => {
        if (option.value !== '') {
          const estadoRecompensa = parseInt(option.dataset.estado);
          if (estadoRecompensa === 1) {
            option.style.display = '';
            option.disabled = false;
          } else {
            option.style.display = 'none';
            option.disabled = true;
          }
        }
      });
    });
    
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

    function filtrarRecompensas() {
      const clienteId = document.getElementById('clienteSelect').value;
      const recompensaSelect = document.getElementById('recompensaSelect');
      const puntosInfo = document.getElementById('puntosInfo');
      const recompensaInfo = document.getElementById('recompensaInfo');
      const btnCrearCanje = document.getElementById('btnCrearCanje');
      
      // Limpiar selección de recompensa
      recompensaSelect.value = '';
      recompensaInfo.style.display = 'none';
      btnCrearCanje.disabled = true;
      
      if (!clienteId) {
        puntosInfo.style.display = 'none';
        // Mostrar solo recompensas activas
        Array.from(recompensaSelect.options).forEach(option => {
          if (option.value !== '') {
            const estadoRecompensa = parseInt(option.dataset.estado);
            if (estadoRecompensa === 1) {
              option.style.display = '';
              option.disabled = false;
            } else {
              option.style.display = 'none';
              option.disabled = true;
            }
          }
        });
        return;
      }
      
      // Buscar datos del cliente
      const cliente = clientesData.find(c => c.clienteId.id == clienteId);
      if (!cliente) {
        document.getElementById('puntosAcumulados').textContent = 0;
        document.getElementById('puntosUtilizados').textContent = 0;
        document.getElementById('puntosDisponibles').textContent = 0;
        puntosInfo.style.display = 'block';
        return;
      }
      // Mostrar información de puntos del cliente
      document.getElementById('puntosAcumulados').textContent = cliente.puntos_acumulados;
      document.getElementById('puntosUtilizados').textContent = cliente.puntos_utilizados;
      document.getElementById('puntosDisponibles').textContent = cliente.puntos_acumulados - cliente.puntos_utilizados;
      puntosInfo.style.display = 'block';
      
      const puntosDisponibles = cliente.puntos_acumulados - cliente.puntos_utilizados;
      
      // Filtrar recompensas según puntos disponibles, stock y estado
      Array.from(recompensaSelect.options).forEach(option => {
        if (option.value === '') return; // Saltar la opción por defecto
        
        const puntosRecompensa = parseInt(option.dataset.puntos);
        const stockRecompensa = parseInt(option.dataset.stock);
        const estadoRecompensa = parseInt(option.dataset.estado);
        
        // Verificar que la recompensa esté activa, tenga stock y el cliente tenga suficientes puntos
        if (estadoRecompensa === 1 && puntosRecompensa <= puntosDisponibles && stockRecompensa > 0) {
          option.style.display = '';
          option.disabled = false;
        } else {
          option.style.display = 'none';
          option.disabled = true;
        }
      });
      
      // Verificar si hay recompensas disponibles
      const recompensasDisponibles = Array.from(recompensaSelect.options).filter(option => 
        option.value !== '' && !option.disabled && option.style.display !== 'none'
      );
      
      const noRecompensasMsg = document.getElementById('noRecompensasMsg');
      if (recompensasDisponibles.length === 0) {
        noRecompensasMsg.style.display = 'block';
        // Actualizar el mensaje para ser más específico
        const puntosDisponibles = cliente.puntos_acumulados - cliente.puntos_utilizados;
        const recompensasActivas = recompensasData.filter(r => r.estado === 1);
        const recompensasConStock = recompensasActivas.filter(r => r.stock > 0);
        const recompensasAccesibles = recompensasConStock.filter(r => r.puntos_requeridos <= puntosDisponibles);
        
        if (recompensasActivas.length === 0) {
          noRecompensasMsg.innerHTML = '<small><i class="bi bi-exclamation-triangle me-1"></i>No hay recompensas activas disponibles.</small>';
        } else if (recompensasConStock.length === 0) {
          noRecompensasMsg.innerHTML = '<small><i class="bi bi-exclamation-triangle me-1"></i>No hay recompensas con stock disponible.</small>';
        } else if (recompensasAccesibles.length === 0) {
          noRecompensasMsg.innerHTML = '<small><i class="bi bi-exclamation-triangle me-1"></i>Este cliente no tiene suficientes puntos para canjear ninguna recompensa disponible.</small>';
        } else {
          noRecompensasMsg.innerHTML = '<small><i class="bi bi-exclamation-triangle me-1"></i>No hay recompensas disponibles para este cliente.</small>';
        }
      } else {
        noRecompensasMsg.style.display = 'none';
      }
      
      // Agregar evento para mostrar información de recompensa seleccionada
      recompensaSelect.onchange = function() {
        const recompensaId = this.value;
        if (recompensaId) {
          const recompensa = recompensasData.find(r => r.id == recompensaId);
          if (recompensa) {
            document.getElementById('puntosRequeridos').textContent = recompensa.puntos_requeridos;
            document.getElementById('stockDisponible').textContent = recompensa.stock;
            document.getElementById('estadoRecompensa').textContent = recompensa.estado === 1 ? 'Activa' : 'Desactivada';
            
            // Calcular puntos restantes después del canje
            const puntosDisponibles = cliente.puntos_acumulados - cliente.puntos_utilizados;
            const puntosRestantes = puntosDisponibles - recompensa.puntos_requeridos;
            document.getElementById('puntosRestantes').textContent = puntosRestantes;
            
            // Cambiar color según si quedan suficientes puntos
            const puntosRestantesElement = document.getElementById('puntosRestantes');
            if (puntosRestantes >= 0) {
              puntosRestantesElement.className = 'text-success fw-bold';
            } else {
              puntosRestantesElement.className = 'text-danger fw-bold';
            }
            
            recompensaInfo.style.display = 'block';
            
            // Solo habilitar el botón si la recompensa está activa
            btnCrearCanje.disabled = recompensa.estado !== 1;
          }
        } else {
          recompensaInfo.style.display = 'none';
          btnCrearCanje.disabled = true;
        }
      };
    }

    // Lógica de ordenamiento
    const ordenarOpciones = document.querySelectorAll('.ordenar-opcion');
    ordenarOpciones.forEach(opcion => {
      opcion.addEventListener('click', function(e) {
        e.preventDefault();
        const sort = this.dataset.sort;
        switch (sort) {
          case 'puntos-desc':
            filteredData.sort((a, b) => b.recompensa.puntos_requeridos - a.recompensa.puntos_requeridos);
            break;
          case 'puntos-asc':
            filteredData.sort((a, b) => a.recompensa.puntos_requeridos - b.recompensa.puntos_requeridos);
            break;
          case 'cliente-az':
            filteredData.sort((a, b) => a.cliente_id.nombre.localeCompare(b.cliente_id.nombre));
            break;
          case 'cliente-za':
            filteredData.sort((a, b) => b.cliente_id.nombre.localeCompare(a.cliente_id.nombre));
            break;
          case 'recompensa-az':
            filteredData.sort((a, b) => a.recompensa.nombre.localeCompare(b.recompensa.nombre));
            break;
          case 'recompensa-za':
            filteredData.sort((a, b) => b.recompensa.nombre.localeCompare(a.recompensa.nombre));
            break;
          case 'fecha-desc':
            filteredData.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
            break;
          case 'fecha-asc':
            filteredData.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
            break;
        }
        currentPage = 1;
        renderTable();
        renderPagination();
      });
    });
  </script>

</body>
</html> 