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
    
    // 1. Encontrar todos os saldos de códigos de barras negativos
    $query = "
        SELECT 
            e.medicamento_id,
            COALESCE(m.nome, CONCAT('Medicamento ID: ', e.medicamento_id)) as medicamento_nome,
            e.clinica_id,
            COALESCE(c.nome, CONCAT('Clínica ID: ', e.clinica_id)) as clinica_nome,
            e.codigo_barras,
            (SUM(CASE WHEN e.tipo = 'Entrada' THEN e.quantidade ELSE 0 END) - SUM(CASE WHEN e.tipo = 'Saida' THEN e.quantidade ELSE 0 END)) as saldo
        FROM estoques e
        LEFT JOIN medicamentos m ON e.medicamento_id = m.id
        LEFT JOIN clinicas c ON e.clinica_id = c.id
        GROUP BY e.medicamento_id, e.codigo_barras, e.clinica_id
        HAVING saldo < 0
    ";
    
    $stmt = $pdo->query($query);
    $negativos = $stmt->fetchAll();
    
    if (empty($negativos)) {
        echo "Nenhum saldo negativo de código de barras encontrado para ajustar!\n";
        exit;
    }
    
    echo "Encontrados " . count($negativos) . " registros negativos de códigos de barras.\n";
    echo "Iniciando a transação de ajuste...\n\n";
    
    $pdo->beginTransaction();
    
    $ajustesRealizados = 0;
    
    foreach ($negativos as $res) {
        $saldoNegativo = (float)$res['saldo'];
        $quantidadeAjuste = abs($saldoNegativo);
        
        // 2. Construir dinamicamente a consulta para buscar metadados de exemplo
        $sqlExemplo = "SELECT lote, dt_vencimento, valor FROM estoques WHERE clinica_id = :clinica_id";
        $paramsExemplo = ['clinica_id' => $res['clinica_id']];
        
        if (is_null($res['medicamento_id'])) {
            $sqlExemplo .= " AND medicamento_id IS NULL";
        } else {
            $sqlExemplo .= " AND medicamento_id = :medicamento_id";
            $paramsExemplo['medicamento_id'] = $res['medicamento_id'];
        }
        
        if (is_null($res['codigo_barras'])) {
            $sqlExemplo .= " AND codigo_barras IS NULL";
        } else {
            $sqlExemplo .= " AND codigo_barras = :codigo_barras";
            $paramsExemplo['codigo_barras'] = $res['codigo_barras'];
        }
        
        $sqlExemplo .= " LIMIT 1";
        
        $stmtExemplo = $pdo->prepare($sqlExemplo);
        $stmtExemplo->execute($paramsExemplo);
        $exemplo = $stmtExemplo->fetch();
        
        // Se não achar por código de barras, busca pelo medicamento e clínica apenas
        if (!$exemplo) {
            $sqlExemplo2 = "SELECT lote, dt_vencimento, valor FROM estoques WHERE clinica_id = :clinica_id";
            $paramsExemplo2 = ['clinica_id' => $res['clinica_id']];
            
            if (is_null($res['medicamento_id'])) {
                $sqlExemplo2 .= " AND medicamento_id IS NULL";
            } else {
                $sqlExemplo2 .= " AND medicamento_id = :medicamento_id";
                $paramsExemplo2['medicamento_id'] = $res['medicamento_id'];
            }
            
            $sqlExemplo2 .= " LIMIT 1";
            
            $stmtExemplo2 = $pdo->prepare($sqlExemplo2);
            $stmtExemplo2->execute($paramsExemplo2);
            $exemplo = $stmtExemplo2->fetch();
        }
        
        $lote = $exemplo ? $exemplo['lote'] : 'AJUSTE';
        $dt_vencimento = $exemplo ? $exemplo['dt_vencimento'] : null;
        $valor = $exemplo ? (float)$exemplo['valor'] : 0.0;
        $total = $valor * $quantidadeAjuste;
        
        echo "Ajustando: Unidade [{$res['clinica_nome']}] | Medicamento [{$res['medicamento_nome']}] | Código Barras [" . ($res['codigo_barras'] ?: '[Vazio]') . "] | Saldo: {$saldoNegativo} -> Novo Lançamento: +{$quantidadeAjuste}\n";
        
        // Inserir registro corretivo
        $stmtInsert = $pdo->prepare("
            INSERT INTO estoques (
                clinica_id, medicamento_id, lote, tipo, quantidade, origem, 
                valor, total, dt_vencimento, codigo_barras, created_at, updated_at
            ) VALUES (
                :clinica_id, :medicamento_id, :lote, 'Entrada', :quantidade, 'Ajuste de Estoque Negativo Barcode',
                :valor, :total, :dt_vencimento, :codigo_barras, NOW(), NOW()
            )
        ");
        
        $stmtInsert->execute([
            'clinica_id' => $res['clinica_id'],
            'medicamento_id' => $res['medicamento_id'],
            'lote' => $lote,
            'quantidade' => $quantidadeAjuste,
            'valor' => $valor,
            'total' => $total,
            'dt_vencimento' => $dt_vencimento,
            'codigo_barras' => $res['codigo_barras']
        ]);
        
        $ajustesRealizados++;
    }
    
    $pdo->commit();
    echo "\nSucesso! {$ajustesRealizados} registros de ajuste criados e consolidados por código de barras no banco de dados.\n";
    
} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erro crítico durante o ajuste: " . $e->getMessage() . "\n";
}
