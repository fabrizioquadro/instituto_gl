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
    
    // 1. Buscar todas as clínicas dinamicamente
    $clinicasStmt = $pdo->query("SELECT nome FROM clinicas ORDER BY nome");
    $clinicas = $clinicasStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Consulta agrupada direta e rápida por código de barras e clínica
    $query = "
        SELECT 
            COALESCE(e.codigo_barras, '[Vazio]') as barcode,
            MAX(e.lote) as lote,
            MAX(e.dt_vencimento) as dt_vencimento,
            c.nome as clinica_nome,
            SUM(CASE WHEN e.tipo = 'Entrada' THEN e.quantidade ELSE 0 END) as entradas,
            SUM(CASE WHEN e.tipo = 'Saida' THEN e.quantidade ELSE 0 END) as saidas,
            (SUM(CASE WHEN e.tipo = 'Entrada' THEN e.quantidade ELSE 0 END) - SUM(CASE WHEN e.tipo = 'Saida' THEN e.quantidade ELSE 0 END)) as saldo
        FROM estoques e
        LEFT JOIN clinicas c ON e.clinica_id = c.id
        WHERE e.medicamento_id = 45
        GROUP BY e.codigo_barras, e.clinica_id
        ORDER BY lote, barcode, clinica_nome
    ";
    
    $stmt = $pdo->query($query);
    $rows = $stmt->fetchAll();
    
    // Agrupar os dados por código de barras para criar uma tabela pivotada (igual ao sistema)
    $pivotData = [];
    foreach ($rows as $row) {
        $barcode = $row['barcode'];
        if (!isset($pivotData[$barcode])) {
            $pivotData[$barcode] = [
                'lote' => $row['lote'] ?: '[Sem Lote]',
                'codigo_barras' => $barcode,
                'vencimento' => $row['dt_vencimento'] ? date('d/m/Y', strtotime($row['dt_vencimento'])) : 'N/D',
                'clinicas' => array_fill_keys($clinicas, 0.0),
                'qt_total' => 0.0
            ];
        }
        $pivotData[$barcode]['clinicas'][$row['clinica_nome']] = (float)$row['saldo'];
        $pivotData[$barcode]['qt_total'] += (float)$row['saldo'];
    }
    
    // Salvar JSON bruto
    file_put_contents(__DIR__ . '/mounjaro_60mg_por_barcode.json', json_encode(array_values($pivotData), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Exibir apenas registros que possuem estoque negativo em pelo menos uma clínica
    echo "=== AUDITORIA DE CÓDIGOS DE BARRAS COM ESTOQUE NEGATIVO (MOUNJARO 60MG) ===\n\n";
    echo "| Lote | C. Barras | Vencimento | QT Total | " . implode(' | ', $clinicas) . " |\n";
    echo "| :--- | :--- | :---: | :---: | " . str_repeat(' :---: |', count($clinicas)) . "\n";
    
    $temNegativoGeral = false;
    foreach ($pivotData as $item) {
        $possuiNegativo = false;
        foreach ($item['clinicas'] as $cNome => $saldo) {
            if ($saldo < 0) {
                $possuiNegativo = true;
                $temNegativoGeral = true;
                break;
            }
        }
        
        if ($possuiNegativo) {
            $rowStr = sprintf(
                "| %s | %s | %s | %.2f",
                $item['lote'],
                $item['codigo_barras'],
                $item['vencimento'],
                $item['qt_total']
            );
            foreach ($clinicas as $cNome) {
                $saldo = $item['clinicas'][$cNome];
                // Destacar o valor se for negativo
                if ($saldo < 0) {
                    $rowStr .= sprintf(" | **%.2f** (🚨)", $saldo);
                } else {
                    $rowStr .= sprintf(" | %.2f", $saldo);
                }
            }
            $rowStr .= " |";
            echo $rowStr . "\n";
        }
    }
    
    if (!$temNegativoGeral) {
        echo "\nNenhum código de barras com estoque negativo encontrado!\n";
    }
    
} catch (\PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
