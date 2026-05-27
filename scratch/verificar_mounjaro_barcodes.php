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
    
    // 1. Buscar todas as clínicas para mapear os nomes corretos
    $clinicasStmt = $pdo->query("SELECT id, nome FROM clinicas");
    $clinicasList = $clinicasStmt->fetchAll();
    $clinicas = [];
    foreach ($clinicasList as $c) {
        $clinicas[$c['id']] = $c['nome'];
    }
    
    // 2. Buscar os códigos de barras distintos do MOUNJARO 60MG (ID 45)
    $stmtCodigos = $pdo->prepare("
        SELECT DISTINCT COALESCE(codigo_barras, '') as barcode 
        FROM estoques 
        WHERE medicamento_id = 45
    ");
    $stmtCodigos->execute();
    $codigos = $stmtCodigos->fetchAll(PDO::FETCH_COLUMN);
    
    $relatorio = [];
    
    foreach ($codigos as $barcode) {
        // Obter os detalhes (lote e dt_vencimento) do primeiro registro deste código de barras
        $stmtInfo = $pdo->prepare("
            SELECT lote, dt_vencimento 
            FROM estoques 
            WHERE medicamento_id = 45 AND COALESCE(codigo_barras, '') = :barcode 
            LIMIT 1
        ");
        $stmtInfo->execute(['barcode' => $barcode]);
        $info = $stmtInfo->fetch();
        
        $lote = $info ? ($info['lote'] ?: '[Sem Lote]') : '[Sem Lote]';
        $vencimento = $info ? ($info['dt_vencimento'] ? date('d/m/Y', strtotime($info['dt_vencimento'])) : 'N/D') : 'N/D';
        
        $item = [
            'lote' => $lote,
            'codigo_barras' => $barcode ?: '[Vazio]',
            'vencimento' => $vencimento,
            'clinicas' => [],
            'qt_total' => 0.0
        ];
        
        // Calcular entradas e saídas em cada clínica para este código de barras
        foreach ($clinicas as $clinicaId => $clinicaNome) {
            $stmtSaldo = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN tipo = 'Entrada' THEN quantidade ELSE 0 END) as entradas,
                    SUM(CASE WHEN tipo = 'Saida' THEN quantidade ELSE 0 END) as saidas
                FROM estoques
                WHERE medicamento_id = 45 
                  AND COALESCE(codigo_barras, '') = :barcode
                  AND clinica_id = :clinica_id
            ");
            $stmtSaldo->execute([
                'barcode' => $barcode,
                'clinica_id' => $clinicaId
            ]);
            $saldoData = $stmtSaldo->fetch();
            
            $entradas = (float)($saldoData['entradas'] ?? 0);
            $saidas = (float)($saldoData['saidas'] ?? 0);
            $saldo = $entradas - $saidas;
            
            $item['clinicas'][$clinicaNome] = $saldo;
            $item['qt_total'] += $saldo;
        }
        
        $relatorio[] = $item;
    }
    
    // Filtrar apenas aqueles que possuem estoque negativo em pelo menos uma clínica
    $relatorioNegativos = [];
    foreach ($relatorio as $item) {
        $temNegativo = false;
        $detalhesNegativos = [];
        foreach ($item['clinicas'] as $clinica => $saldo) {
            if ($saldo < 0) {
                $temNegativo = true;
                $detalhesNegativos[] = "$clinica ($saldo)";
            }
        }
        if ($temNegativo) {
            $item['negativos_detalhes'] = implode(', ', $detalhesNegativos);
            $relatorioNegativos[] = $item;
        }
    }
    
    // Salvar JSON do relatório de códigos de barras completos
    file_put_contents(__DIR__ . '/mounjaro_60mg_por_barcode.json', json_encode($relatorio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "=== RELATÓRIO DE ESTOQUES NEGATIVOS POR CÓDIGO DE BARRAS ===\n\n";
    echo "| Lote | C. Barras | Vencimento | QT Total | " . implode(' | ', array_values($clinicas)) . " | Negativos em |\n";
    echo "| :--- | :--- | :---: | :---: | " . str_repeat(' :---: |', count($clinicas)) . " :--- |\n";
    
    foreach ($relatorioNegativos as $item) {
        $row = sprintf(
            "| %s | %s | %s | %.1f",
            $item['lote'],
            $item['codigo_barras'],
            $item['vencimento'],
            $item['qt_total']
        );
        foreach ($clinicas as $clinicaId => $clinicaNome) {
            $saldo = $item['clinicas'][$clinicaNome];
            $row .= sprintf(" | %.1f", $saldo);
        }
        $row .= " | " . $item['negativos_detalhes'] . " |";
        echo $row . "\n";
    }
    
} catch (\PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
