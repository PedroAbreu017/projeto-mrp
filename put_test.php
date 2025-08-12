<?php
$ch = curl_init('http://localhost:8000/api/components/1');

$data = [
    'nome' => 'Rodas Atualizadas',
    'descricao' => 'Nova descrição',
    'quantidade_estoque' => 200
];

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
]);

$response = curl_exec($ch);
echo $response;
curl_close($ch);
