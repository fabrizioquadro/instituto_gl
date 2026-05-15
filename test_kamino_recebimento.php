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

$cpf = '43310536874'; // Paciente: Naerte

echo "1. Buscando paciente pelo CPF $cpf no Sandbox...\n";
$url_busca = "https://sandbox.kamino.tech/api/pessoa/lista/paginada?CPFCNPJ=$cpf";

try {
    $res = $client->request('GET', $url_busca, ['headers' => $headers]);
    $dados = json_decode($res->getBody(), true);
    
    // Debug da resposta
    echo "Estrutura da resposta de busca:\n";
    print_r($dados);

    if (empty($dados) || (isset($dados['Lista']) && empty($dados['Lista']))) {
        echo "Paciente não encontrado no Sandbox da Kamino. Vamos tentar cadastrar...\n";
        // ... (resto do código)
        
        $url_cadastro = "https://sandbox.kamino.tech/api/pessoa/grava";
        $corpo_cadastro = [
            'Nome' => 'Naerte Teste Sandbox',
            'CPFCNPJ' => $cpf,
            'Cliente' => true
        ];

        $res_cad = $client->request('POST', $url_cadastro, [
            'headers' => $headers,
            'json' => $corpo_cadastro
        ]);
        
        $dados_cad = json_decode($res_cad->getBody(), true);
        $idPessoa = $dados_cad['ID'];
        echo "Paciente cadastrado com sucesso! ID: $idPessoa\n";
    } else {
        // Se a resposta for um array de resultados, pegamos o primeiro
        $idPessoa = isset($dados[0]['ID']) ? $dados[0]['ID'] : $dados['ID'];
        echo "Sucesso! ID da Pessoa na Kamino: $idPessoa\n";
    }

    echo "2. Lançando Conta a Receber de R$ 150,00...\n";
    $url_recebimento = "https://sandbox.kamino.tech/api/financeiro/recebimento";
    
    // Estrutura baseada na documentação encontrada
    $body = [
        'ID' => 0, // 0 indica novo registro
        'IDPessoa' => $idPessoa,
        'VlrVenc' => 150.00,
        'VlrBruto' => 150.00,
        'DtaVenc' => date('Y-m-d', strtotime('+30 days')),
        'SitConta' => 1, // 1 = Pendente
        'Descri' => 'Teste de Conta a Receber - Integracao GL',
        'CodigoExterno' => 'TESTE-' . time()
    ];

    $res = $client->request('POST', $url_recebimento, [
        'headers' => $headers,
        'json' => $body
    ]);

    echo "Status Code: " . $res->getStatusCode() . "\n";
    echo "Resposta Final: " . $res->getBody() . "\n";

} catch (\Exception $e) {
    echo "Erro durante o teste: " . $e->getMessage() . "\n";
}
