<?php
$host = '193.203.175.219';
$db   = 'u528878205_sistema';
$user = 'u528878205_sistema';
$pass = 'J?QI!KAd4z';
$port = '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Buscar todos os lançamentos do MOUNJARO 60MG (ID 45)
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.clinica_id,
            c.nome AS clinica_nome,
            e.lote,
            e.tipo,
            e.quantidade,
            e.origem,
            e.created_at
        FROM estoques e
        LEFT JOIN clinicas c ON e.clinica_id = c.id
        WHERE e.medicamento_id = 45
        ORDER BY clinica_nome, e.lote, e.created_at ASC
    ");
    $stmt->execute();
    $lancamentos = $stmt->fetchAll();
    
    file_put_contents(__DIR__ . '/mounjaro_60mg_movimentacoes.json', json_encode($lancamentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "SALVO_SUCESSO\n";
    
    // Contagem de lançamentos por origem
    $origens = [];
    foreach ($lancamentos as $l) {
        $origens[$l['origem']] = ($origens[$l['origem']] ?? 0) + 1;
    }
    
    echo "=== RESUMO DE ENTRADAS/SAÍDAS POR ORIGEM ===\n";
    foreach ($origens as $origem => $qtd) {
        echo "Origem: " . ($origem ?: '[Vazia]') . " | Quantidade de registros: $qtd\n";
    }
    
} catch (\PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
