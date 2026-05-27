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
    
    // 1. Encontrar todos os medicamentos relacionados a MOUNJARO
    $stmt = $pdo->prepare("SELECT id, nome, fabricante FROM medicamentos WHERE nome LIKE :nome");
    $stmt->execute(['nome' => '%MOUNJARO%']);
    $medicamentos = $stmt->fetchAll();
    
    echo "=== MEDICAMENTOS MOUNJARO REGISTRADOS ===\n";
    foreach ($medicamentos as $m) {
        echo "ID: {$m['id']} | Nome: {$m['nome']} | Fabricante: {$m['fabricante']}\n";
    }
    echo "=========================================\n\n";
    
    // 2. Consulta de estoque detalhado
    $query = "
        SELECT 
            e.clinica_id,
            COALESCE(c.nome, CONCAT('Clínica ID: ', e.clinica_id)) AS clinica_nome,
            e.medicamento_id,
            COALESCE(m.nome, CONCAT('Medicamento ID: ', e.medicamento_id)) AS medicamento_nome,
            COALESCE(m.fabricante, 'N/D') AS medicamento_fabricante,
            e.lote,
            SUM(CASE WHEN e.tipo = 'Entrada' THEN e.quantidade ELSE 0 END) as total_entrada,
            SUM(CASE WHEN e.tipo = 'Saida' THEN e.quantidade ELSE 0 END) as total_saida,
            (SUM(CASE WHEN e.tipo = 'Entrada' THEN e.quantidade ELSE 0 END) - SUM(CASE WHEN e.tipo = 'Saida' THEN e.quantidade ELSE 0 END)) as saldo
        FROM estoques e
        LEFT JOIN clinicas c ON e.clinica_id = c.id
        LEFT JOIN medicamentos m ON e.medicamento_id = m.id
        WHERE m.nome LIKE :mounjaro_name
        GROUP BY e.clinica_id, e.medicamento_id, e.lote
        ORDER BY clinica_nome, m.nome, e.lote
    ";
    
    $stmtEstoque = $pdo->prepare($query);
    $stmtEstoque->execute(['mounjaro_name' => '%MOUNJARO%']);
    $estoqueItems = $stmtEstoque->fetchAll();
    
    // Salvar JSON bruto
    file_put_contents(__DIR__ . '/mounjaro_estoque.json', json_encode($estoqueItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "=== TABELA DE ESTOQUES DE MOUNJARO ===\n\n";
    echo "| Clínica | Medicamento | Fabricante | Lote | Entradas | Saídas | Saldo | Situação |\n";
    echo "| :--- | :--- | :--- | :--- | :---: | :---: | :---: | :--- |\n";
    
    foreach ($estoqueItems as $item) {
        $situacao = "OK";
        if ($item['saldo'] < 0) {
            $situacao = "🚨 NEGATIVO";
        } elseif ($item['saldo'] == 0) {
            $situacao = "ZERADO";
        }
        
        printf(
            "| %s | %s | %s | %s | %.2f | %.2f | %.2f | %s |\n",
            $item['clinica_nome'],
            $item['medicamento_nome'],
            $item['medicamento_fabricante'],
            $item['lote'] ?? '[Sem Lote]',
            $item['total_entrada'],
            $item['total_saida'],
            $item['saldo'],
            $situacao
        );
    }
    
} catch (\PDOException $e) {
    echo "Erro de conexão/consulta: " . $e->getMessage() . "\n";
}
