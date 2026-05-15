<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();

$headers = [
    'App' => 'bd396410-d4b5-477a-add7-c0afe9a445f3',
    'CN' => 'InstitutoGL5231',
    'Hash' => 'gIJCSkZEQD46gkSARTpER0d+On6Cgkc6gT5+hYRKfkRERYVChUaEPkqASYQ6gT5FgDpER0dKOn5CQX46RIFESUpHfoWAREpJiY+Wl4mXmZeRho1FQUJAQQ==',
    'IDUsr' => '2',
    'Usr' => 'f6e09b8e-c05b-4779-a32a-4c4897afb498',
    'accept' => 'application/json',
    'content-type' => 'application/json',
];

// Vamos testar o endpoint de listagem de pessoas no ambiente de SANDBOX (que sabemos que é GET)
$url = 'https://sandbox.kamino.tech/api/pessoa/lista/paginada';

echo "Testando conexão com o Sandbox da Kamino...\n";

try {
    $response = $client->request('GET', $url, [
        'headers' => $headers,
        'http_errors' => false // Para não travar no catch em caso de 401/403
    ]);

    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 200) {
        echo "Sucesso! O token é válido para o Sandbox.\n";
        // echo "Resposta: " . substr($response->getBody(), 0, 100) . "...\n";
    } else {
        echo "Erro de Autenticação ou Acesso Negado.\n";
        echo "Resposta: " . $response->getBody() . "\n";
    }
} catch (\Exception $e) {
    echo "Erro inesperado: " . $e->getMessage() . "\n";
}
