<?php
require __DIR__ . '/../vendor/autoload.php';

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmZWVnb3ciLCJhdWQiOiJwdWJsaWNhcGkiLCJpYXQiOjE3NTE4OTczODYsImxpY2Vuc2VJRCI6MjMyMjR9.ZC8gSWEiCJsLa7AoFUOT074zaRNECddfXJNT_zi8RvI";

// Try different date formats and endpoints
$testes = [
    ['url' => 'https://api.feegow.com/v1/api/appoints/list', 'params' => ['data' => '30-07-2026', 'limit' => 3]],
    ['url' => 'https://api.feegow.com/v1/api/appoints/list', 'params' => ['data' => '30/07/2026', 'limit' => 3]],
    ['url' => 'https://api.feegow.com/v1/api/appoints/list', 'params' => ['data_inicio' => '30-07-2026', 'limit' => 3]],
];

foreach ($testes as $i => $teste) {
    echo "\n--- Teste " . ($i+1) . " ---\n";
    $apiUrl = $teste['url'] . '?' . http_build_query($teste['params']);
    echo "URL: $apiUrl\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-access-token: $token",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP: $httpCode\n";
    $retorno = json_decode($response, true);
    if ($retorno === null && $response) {
        echo "Resposta (não-JSON): " . substr($response, 0, 500) . "\n";
    } else {
        echo "success: " . ($retorno['success'] ?? 'N/A') . "\n";
        if (isset($retorno['message'])) echo "message: " . $retorno['message'] . "\n";
        $content = $retorno['content'] ?? [];
        if (is_array($content)) {
            $json = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo "content: " . substr($json, 0, 1000) . "\n";
        }
    }
}
