<?php
// Archivo de prueba para verificar los movimientos de caja

// Obtener movimientos de caja
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/movimientosCaja',
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
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

echo "Respuesta de la API de movimientos de caja:\n";
echo "HTTP Code: " . $httpCode . "\n";
echo "CURL Error: " . $curlError . "\n";
echo "Response: " . $response . "\n\n";

// Procesar los datos
$movimientos = json_decode($response, true);
if (is_array($movimientos)) {
    echo "Total de movimientos: " . count($movimientos) . "\n\n";
    
    // Filtrar por empresa (ejemplo con empresa ID 5)
    $empId = 5;
    $movimientosEmpresa = array_filter($movimientos, function($movimiento) use ($empId) {
        return isset($movimiento['caja']['sucursalId']['empresa']['id']) && 
               $movimiento['caja']['sucursalId']['empresa']['id'] == $empId;
    });
    
    echo "Movimientos de la empresa ID $empId: " . count($movimientosEmpresa) . "\n\n";
    
    // Filtrar por fecha de hoy
    $movimientosHoy = array_filter($movimientosEmpresa, function($movimiento) {
        return date('Y-m-d') === date('Y-m-d', strtotime($movimiento['fecha']));
    });
    
    echo "Movimientos de hoy: " . count($movimientosHoy) . "\n\n";
    
    // Calcular totales
    $total_movimientos = 0;
    $movimientos_ingresos = 0;
    $movimientos_egresos = 0;
    
    foreach ($movimientosHoy as $movimiento) {
        $total_movimientos += abs($movimiento['monto']);
        if ($movimiento['monto'] > 0) {
            $movimientos_ingresos += $movimiento['monto'];
        } else {
            $movimientos_egresos += abs($movimiento['monto']);
        }
    }
    
    echo "Estadísticas del día:\n";
    echo "- Total movimientos: S/ " . number_format($total_movimientos, 2) . "\n";
    echo "- Ingresos: S/ " . number_format($movimientos_ingresos, 2) . "\n";
    echo "- Egresos: S/ " . number_format($movimientos_egresos, 2) . "\n\n";
    
    // Mostrar detalles de los movimientos
    echo "Detalles de movimientos de hoy:\n";
    foreach (array_values($movimientosHoy) as $index => $movimiento) {
        echo ($index + 1) . ". ";
        echo "Caja #" . $movimiento['caja']['id'] . " - ";
        echo $movimiento['caja']['sucursalId']['nombre'] . " - ";
        echo $movimiento['tipo'] . " - ";
        echo "S/ " . number_format($movimiento['monto'], 2) . " - ";
        echo $movimiento['descripcion'] . " - ";
        echo date('H:i', strtotime($movimiento['fecha'])) . "\n";
    }
} else {
    echo "Error: No se pudieron decodificar los datos JSON\n";
}
?> 