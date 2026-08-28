<?php
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmZWVnb3ciLCJhdWQiOiJwdWJsaWNhcGkiLCJpYXQiOjE3NTE4OTczODYsImxpY2Vuc2VJRCI6MjMyMjR9.ZC8gSWEiCJsLa7AoFUOT074zaRNECddfXJNT_zi8RvI";

function call_feegow($method, $params) {
    global $token;
    $url = "https://api.feegow.com/v1/api/" . $method . "?" . http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-access-token: " . $token, "Content-Type: application/json"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["http" => $httpCode, "body" => $response];
}

// Test 1: with local_id
echo "Test 1 - with local_id:\n";
$r = call_feegow("appoints/list", ["data" => "30-07-2026", "local_id" => 1, "limit" => 3]);
echo "HTTP: " . $r["http"] . "\n" . $r["body"] . "\n\n";

// Test 2: POST
echo "Test 2 - POST:\n";
$ch = curl_init("https://api.feegow.com/v1/api/appoints/list");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["data" => "30-07-2026", "limit" => 3]));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-access-token: " . $token, "Content-Type: application/json"]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: " . $httpCode . "\n" . $response . "\n\n";

// Test 3: agenda endpoint
echo "Test 3 - agenda:\n";
$r = call_feegow("agenda", ["data" => "30-07-2026"]);
echo "HTTP: " . $r["http"] . "\n" . $r["body"] . "\n\n";

// Test 4: appoints/status (known working endpoint)
echo "Test 4 - appoints/status (known working):\n";
$r = call_feegow("appoints/status", []);
echo "HTTP: " . $r["http"] . "\n" . $r["body"] . "\n\n";

// Test 5: try with Guzzle for better error
echo "Test 5 - Guzzle:\n";
require __DIR__ . "/../vendor/autoload.php";
try {
    $client = new GuzzleHttp\Client();
    $resp = $client->request("GET", "https://api.feegow.com/v1/api/appoints/list", [
        "headers" => ["x-access-token" => $token, "Content-Type" => "application/json"],
        "query" => ["data" => "30-07-2026", "limit" => 3]
    ]);
    echo "HTTP: " . $resp->getStatusCode() . "\n" . $resp->getBody() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (method_exists($e, "hasResponse") && $e->hasResponse()) {
        echo "Response: " . $e->getResponse()->getBody() . "\n";
    }
}
