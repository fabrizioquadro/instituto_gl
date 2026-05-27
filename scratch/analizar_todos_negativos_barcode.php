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
    
    // Consulta agrupando por medicamento, código de barras e clínica para achar saldos negativos
    $query = "
        SELECT 
            e.medicamento_id,
            COALESCE(m.nome, CONCAT('Medicamento ID: ', e.medicamento_id)) as medicamento_nome,
            m.fabricante as medicamento_fabricante,
            e.clinica_id,
            COALESCE(c.nome, CONCAT('Clínica ID: ', e.clinica_id)) as clinica_nome,
            COALESCE(e.codigo_barras, '') as barcode,
            MAX(e.lote) as lote,
            MAX(e.dt_vencimento) as dt_vencimento,
            SUM(CASE WHEN e.tipo = 'Entrada' THEN e.quantidade ELSE 0 END) as total_entrada,
            SUM(CASE WHEN e.tipo = 'Saida' THEN e.quantidade ELSE 0 END) as total_saida,
            (SUM(CASE WHEN e.tipo = 'Entrada' THEN e.quantidade ELSE 0 END) - SUM(CASE WHEN e.tipo = 'Saida' THEN e.quantidade ELSE 0 END)) as saldo
        FROM estoques e
        LEFT JOIN medicamentos m ON e.medicamento_id = m.id
        LEFT JOIN clinicas c ON e.clinica_id = c.id
        GROUP BY e.medicamento_id, e.codigo_barras, e.clinica_id
        HAVING saldo < 0
        ORDER BY clinica_nome, medicamento_nome, lote, barcode
    ";
    
    $stmt = $pdo->query($query);
    $negativos = $stmt->fetchAll();
    
    file_put_contents(__DIR__ . '/todos_negativos_por_barcode.json', json_encode($negativos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "=== LEITURA DE SALDOS NEGATIVOS CONCLUÍDA ===\n";
    echo "Total de linhas com saldo de código de barras negativo: " . count($negativos) . "\n\n";
    
    // Agrupamento para resumo no console
    $resumoPorMedicamento = [];
    $resumoPorClinica = [];
    
    foreach ($negativos as $n) {
        $medName = $n['medicamento_nome'];
        $clinName = $n['clinica_nome'];
        
        $resumoPorMedicamento[$medName] = ($resumoPorMedicamento[$medName] ?? 0) + 1;
        $resumoPorClinica[$clinName] = ($resumoPorClinica[$clinName] ?? 0) + 1;
    }
    
    echo "=== OCORRÊNCIAS POR MEDICAMENTO ===\n";
    arsort($resumoPorMedicamento);
    foreach ($resumoPorMedicamento as $med => $qtd) {
        echo "Medicamento: $med | Barcodes negativos: $qtd\n";
    }
    
    echo "\n=== OCORRÊNCIAS POR CLÍNICA ===\n";
    arsort($resumoPorClinica);
    foreach ($resumoPorClinica as $clin => $qtd) {
        echo "Clínica: $clin | Barcodes negativos: $qtd\n";
    }
    
} catch (\PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
