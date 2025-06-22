<?php
// Archivo de prueba para verificar el cierre de caja usando PUT

// Datos de ejemplo para cerrar una caja existente
$payloadEjemplo = [
    "id" => 1, // ID de la caja a cerrar
    "sucursalId" => 1,
    "usuarioId" => 1,
    "apertura" => "2025-06-05T08:00:00",
    "cierre" => "2025-06-05T17:00:00",
    "montoInicial" => 100.00,
    "montoFinal" => 250.00,
    "estado" => 0 // 0 = cerrada
];

echo "Payload de ejemplo para cerrar caja:\n";
echo json_encode($payloadEjemplo, JSON_PRETTY_PRINT);
echo "\n\n";

// Prueba de cierre de caja usando PUT
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/caja',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS => json_encode($payloadEjemplo),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
    ),
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

echo "Respuesta de la API (PUT - Cerrar caja):\n";
echo "HTTP Code: " . $httpCode . "\n";
echo "CURL Error: " . $curlError . "\n";
echo "Response: " . $response . "\n";

// También probar con POST para comparar
echo "\n\n--- Probando con POST (debería crear nueva caja) ---\n";
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://ropas.spring.informaticapp.com:1688/api/ropas/caja',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($payloadEjemplo),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI5ZmNjYjFhZTI2NjNlOTI0OWZmMDE4MTFmMmMwNzliNmUwNjc1MzNkZTJkNzZjZjhkMDViMTQ2YmE2YzM2N2YzIiwiaWF0IjoxNzUwMjg0ODI0LCJleHAiOjQ5MDM4ODQ4MjR9.k2nd5JJHRfOHUfPhyq7xAwRFledNZGQYQYFqThyTDII'
    ),
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

echo "Respuesta de la API (POST - Crear nueva caja):\n";
echo "HTTP Code: " . $httpCode . "\n";
echo "CURL Error: " . $curlError . "\n";
echo "Response: " . $response . "\n";
?> 