<?php
// Archivo de prueba para verificar la estructura de la API de caja

// Datos de ejemplo basados en la respuesta de la API
$payloadEjemplo = [
    "sucursalId" => 1,
    "usuarioId" => 1,
    "apertura" => "2025-06-05T08:00:00",
    "cierre" => null,
    "montoInicial" => 100.00,
    "montoFinal" => null,
    "estado" => 1
];

echo "Payload de ejemplo para crear caja:\n";
echo json_encode($payloadEjemplo, JSON_PRETTY_PRINT);
echo "\n\n";

// Prueba de creación de caja
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

echo "Respuesta de la API:\n";
echo "HTTP Code: " . $httpCode . "\n";
echo "CURL Error: " . $curlError . "\n";
echo "Response: " . $response . "\n";
?> 