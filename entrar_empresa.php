<?php
session_start();

// 1. Verificar si el usuario es SUPERadmin
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'SUPERadmin') {
    header('Location: index.php');
    exit;
}

// 2. Verificar si se proporcionó un ID de empresa
if (!isset($_GET['id'])) {
    header('Location: empresas.php');
    exit;
}

$empresaId = (int)$_GET['id'];

// 3. Incluir configuración y funciones de la API
define('API_BASE', 'http://ropas.spring.informaticapp.com:1655/api/ropas');
define('API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII');

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
  curl_close($ch);
  
  return json_decode($response, true);
}

// 4. Obtener información de la empresa
$empresas = apiRequest(API_BASE . '/empresas');
$empresa = null;
foreach ($empresas as $e) {
    if ($e['id'] === $empresaId) {
        $empresa = $e;
        break;
    }
}

if (!$empresa) {
    $_SESSION['message'] = "Empresa no encontrada.";
    $_SESSION['messageType'] = "danger";
    header('Location: empresas.php');
    exit;
}

// 5. Buscar un usuario ADMIN para la empresa objetivo
$usuarios = apiRequest(API_BASE . '/usuarios');
$admin_para_impersonar = null;

if (is_array($usuarios)) {
    foreach ($usuarios as $usuario) {
        if (isset($usuario['empresa']['id']) && $usuario['empresa']['id'] === $empresaId && $usuario['rol'] === 'ADMIN') {
            $admin_para_impersonar = $usuario;
            break;
        }
    }
}

// 6. Si se encuentra un admin, realizar la suplantación
if ($admin_para_impersonar) {
    // Guardar la sesión original del SUPERadmin
    $_SESSION['superadmin_original_user'] = $_SESSION['user'];
    
    // Establecer la sesión del ADMIN de la empresa
    $_SESSION['user'] = $admin_para_impersonar;
    
    // Guardar mensaje de éxito para mostrar en el dashboard
    $_SESSION['message'] = "Has ingresado a la empresa <strong>" . htmlspecialchars($empresa['nombre'], ENT_QUOTES) . "</strong> como <strong>" . htmlspecialchars($admin_para_impersonar['nombre'], ENT_QUOTES) . "</strong>.";
    $_SESSION['messageType'] = 'success';
    
    // Redirigir al dashboard general, que ahora manejará la vista de ADMIN
    header('Location: dashboard.php');
    exit;
} else {
    // 7. Si no se encuentra un admin, mostrar alerta y redirigir
    $_SESSION['message'] = "La empresa <strong>" . htmlspecialchars($empresa['nombre']) . "</strong> no tiene un usuario ADMIN creado. Debes crear un usuario ADMIN antes de poder ingresar a la empresa.";
    $_SESSION['messageType'] = "warning";
    header('Location: empresas.php');
    exit;
} 