<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\ApiFlegowController;

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmZWVnb3ciLCJhdWQiOiJwdWJsaWNhcGkiLCJpYXQiOjE3NTE4OTczODYsImxpY2Vuc2VJRCI6MjMyMjR9.ZC8gSWEiCJsLa7AoFUOT074zaRNECddfXJNT_zi8RvI";

$paciente_id = $argv[1] ?? 16988; // default: paciente da tarefa anterior
$data_inicio = $argv[2] ?? '01-06-2026';
$data_fim = $argv[3] ?? '10-08-2026';

echo "== TESTE 1: appoints/search com paciente_id =========\n";
$parametros = [
    'data_start' => $data_inicio,
    'data_end' => $data_fim,
    'paciente_id' => $paciente_id,
];
$apiUrl = "https://api.feegow.com/v1/api/appoints/search?" . http_build_query($parametros);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-access-token: $token", "Content-Type: application/json"]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
if ($err) {
    echo "Erro cURL: $err\n";
} else {
    echo "HTTP $httpCode\n";
    $retorno = json_decode($response, true);
    if (isset($retorno['success']) && $retorno['success']) {
        $content = $retorno['content'];
        $total = count($content);
        echo "success: true | agendamentos retornados: $total\n";
        foreach ($content as $a) {
            $a_pac = $a['paciente_id'] ?? '?';
            echo "  - agendamento_id: " . ($a['agendamento_id'] ?? '?') . " | paciente_id: $a_pac | data: " . ($a['data'] ?? '?') . " | horario: " . ($a['horario'] ?? '?') . " | status_id: " . ($a['status_id'] ?? '?') . "\n";
        }
    } else {
        echo "success: false\n";
        print_r($retorno);
    }
}
