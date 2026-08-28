<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmZWVnb3ciLCJhdWQiOiJwdWJsaWNhcGkiLCJpYXQiOjE3NTE4OTczODYsImxpY2Vuc2VJRCI6MjMyMjR9.ZC8gSWEiCJsLa7AoFUOT074zaRNECddfXJNT_zi8RvI";

$parametros = [
    'data_start' => '01-06-2026',
    'data_end' => '10-08-2026',
    'paciente_id' => 16988,
];
$apiUrl = "https://api.feegow.com/v1/api/appoints/search?" . http_build_query($parametros);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-access-token: $token", "Content-Type: application/json"]);
$response = curl_exec($ch);
curl_close($ch);

$retorno = json_decode($response, true);
if (!isset($retorno['success']) || !$retorno['success']) {
    print_r($retorno);
    exit(1);
}

$content = $retorno['content'];
echo "Total: " . count($content) . "\n\n";
echo "Estrutura completa do 1º agendamento:\n";
echo json_encode($content[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// Também buscar status da Feegow para mapear status_id
echo "\n===== STATUS DA FEEGOW =====\n";
$urlStatus = "https://api.feegow.com/v1/api/appoints/status";
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $urlStatus);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch2, CURLOPT_HTTPHEADER, ["x-access-token: $token", "Content-Type: application/json"]);
$respStatus = curl_exec($ch2);
curl_close($ch2);
$retornoStatus = json_decode($respStatus, true);
echo json_encode($retornoStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
