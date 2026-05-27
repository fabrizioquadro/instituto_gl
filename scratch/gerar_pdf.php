<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Dompdf\Dompdf;
use Dompdf\Options;

echo "--- RENDERIZANDO PDF PREMIUM CORRIGIDO (CSS DE TABELAS DOMPDF) ---\n";

$jsonPath = __DIR__ . '/relatorio_saldo_negativo.json';
if (!file_exists($jsonPath)) {
    die("Erro: Arquivo JSON de relatório não encontrado. Execute o script de análise primeiro.\n");
}

$relatorio = json_decode(file_get_contents($jsonPath), true);

// Calcular estatísticas dinâmicas
$totalUnidades = count($relatorio);
$totalLotesNegativos = 0;
$totalVolumeNegativo = 0;

foreach ($relatorio as $clinica => $itens) {
    $totalLotesNegativos += count($itens);
    foreach ($itens as $item) {
        $totalVolumeNegativo += abs($item['saldo']);
    }
}

$dataAtual = date('d/m/Y H:i');

// Iniciar HTML corrigido (compatível 100% com motor de renderização da Dompdf)
$html = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Auditoria de Estoque - Saldos Negativos</title>
    <style>
        @page {
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
        }
        body {
            font-family: "Helvetica", "Arial", sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.4;
            margin-top: 0.6cm;
            margin-bottom: 0.6cm;
            padding: 0;
        }
        
        /* Cabeçalho e Rodapé Fixos (Dompdf exige pos:fixed antes do conteúdo) */
        .header {
            position: fixed;
            top: -1cm;
            left: 0;
            right: 0;
            height: 0.8cm;
            border-bottom: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
        }
        .header-left {
            float: left;
            font-weight: bold;
        }
        .header-right {
            float: right;
        }
        
        .footer {
            position: fixed;
            bottom: -1cm;
            left: 0;
            right: 0;
            height: 0.8cm;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 8px;
        }
        .footer-left {
            float: left;
            margin-top: 4px;
        }
        .footer-right {
            float: right;
            margin-top: 4px;
        }
        
        /* Título Principal */
        .title-container {
            margin-bottom: 0.6cm;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .main-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sub-title {
            font-size: 10px;
            color: #64748b;
            margin: 4px 0 0 0;
        }
        
        /* Painel de KPIs usando Tabela (Evita floats bugados no Dompdf) */
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 0.6cm;
        }
        .kpi-card-cell {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }
        .kpi-card-danger-cell {
            background-color: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .kpi-value.danger {
            color: #dc2626;
        }
        .kpi-label {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        /* Diagnóstico e Alertas */
        .alert-box {
            background-color: #fffbeb;
            border-left: 4px solid #d97706;
            color: #92400e;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 0.6cm;
        }
        .alert-title {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 10.5px;
        }
        .alert-desc {
            font-size: 9px;
            margin: 0;
        }
        
        /* Tabelas e Conteúdo */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4px;
            margin-top: 0.5cm;
            margin-bottom: 0.3cm;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.6cm;
            page-break-inside: auto; /* Permite quebrar páginas */
        }
        .stock-table thead {
            display: table-header-group; /* Repete cabeçalho no topo de cada página nova */
        }
        .stock-table tr {
            page-break-inside: avoid; /* Evita quebra no meio de uma linha */
            page-break-after: auto;
        }
        .stock-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        .stock-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
            color: #334155;
        }
        .stock-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        
        /* Auxiliares */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-danger { color: #dc2626; font-weight: bold; }
        .text-warning { color: #d97706; font-weight: bold; }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    <!-- Cabeçalho Fixo -->
    <div class="header">
        <div class="header-left">Instituto GL — Relatório de Auditoria de Estoque</div>
        <div class="header-right">Gerado: ' . $dataAtual . '</div>
    </div>

    <!-- Rodapé Fixo -->
    <div class="footer">
        <div class="footer-left">Relatório Técnico Confidencial — Uso Interno</div>
        <div class="footer-right">Página</div>
    </div>

    <!-- Título Principal -->
    <div class="title-container">
        <h1 class="main-title">Auditoria de Saldos Negativos</h1>
        <p class="sub-title">Movimentações de Estoque com Quantidade de Saída Superior às Entradas por Lote</p>
    </div>

    <!-- Painel de KPIs via Tabela Sem Bordas -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card-cell" width="31%">
                <div class="kpi-value">' . $totalUnidades . '</div>
                <div class="kpi-label">Unidades Clínicas</div>
            </td>
            <td width="3.5%"></td>
            <td class="kpi-card-cell" width="31%">
                <div class="kpi-value">' . $totalLotesNegativos . '</div>
                <div class="kpi-label">Lotes Negativos</div>
            </td>
            <td width="3.5%"></td>
            <td class="kpi-card-danger-cell" width="31%">
                <div class="kpi-value danger">-' . number_format($totalVolumeNegativo, 1, ',', '.') . '</div>
                <div class="kpi-label" style="color: #9b2c2c;">Déficit Total (Unidades)</div>
            </td>
        </tr>
    </table>

    <!-- Bloco de Diagnóstico -->
    <div class="alert-box">
        <div class="alert-title">⚠️ DIAGNÓSTICO OPERACIONAL E INCONSISTÊNCIAS DE INTEGRIDADE</div>
        <p class="alert-desc">
            Os saldos negativos representam uma inversão cronológica nos lançamentos (onde baixas e aplicações em procedimentos médicos são computadas no sistema antes das respectivas Notas Fiscais de entrada serem registradas) ou digitação divergente na grafia dos lotes (gerando lotes fantasmas). Adicionalmente, foram identificados registros com <strong>Medicamento ID Nulo (Órfãos)</strong> que requerem intervenção manual no banco de dados.
        </p>
    </div>
';

$isFirst = true;
foreach ($relatorio as $clinica => $itens) {
    if (!$isFirst) {
        $html .= '<div class="page-break"></div>';
    }
    $isFirst = false;

    $html .= '
    <div class="section-title">🏢 UNIDADE: ' . htmlspecialchars(strtoupper($clinica)) . '</div>
    <table class="stock-table">
        <thead>
            <tr>
                <th width="35%">Medicamento</th>
                <th width="15%">Fabricante</th>
                <th width="15%">Lote</th>
                <th width="11%" class="text-center">Total Entradas</th>
                <th width="11%" class="text-center">Total Saídas</th>
                <th width="13%" class="text-right">Saldo Atual</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($itens as $item) {
        $nomeMed = htmlspecialchars($item['medicamento']);
        $fab = htmlspecialchars($item['fabricante']);
        $lote = htmlspecialchars($item['lote']);
        
        if (is_null($item['medicamento_id'])) {
            $nomeMed = '<span class="text-warning">⚠️ [ID NULO - ÓRFÃO]</span>';
            $fab = '<span class="text-warning">N/D</span>';
        }
        
        $html .= '
            <tr>
                <td class="font-bold">' . $nomeMed . '</td>
                <td>' . $fab . '</td>
                <td><code>' . $lote . '</code></td>
                <td class="text-center">' . number_format($item['total_entrada'], 1, ',', '.') . '</td>
                <td class="text-center">' . number_format($item['total_saida'], 1, ',', '.') . '</td>
                <td class="text-right text-danger">' . number_format($item['saldo'], 1, ',', '.') . '</td>
            </tr>';
    }

    $html .= '
        </tbody>
    </table>';
}

$html .= '
</body>
</html>';

try {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $output = $dompdf->output();
    
    // Salvar na raiz (para conveniência do usuário)
    $outputPathRoot = __DIR__ . '/../relatorio_saldo_negativo.pdf';
    file_put_contents($outputPathRoot, $output);
    
    // Salvar na pasta public (para acesso via Apache HTTP)
    $outputPathPublic = __DIR__ . '/../public/relatorio_saldo_negativo.pdf';
    file_put_contents($outputPathPublic, $output);
    
    echo "PDF_GERADO_SUCESSO\n";
    echo "Salvo na Raiz: " . realpath($outputPathRoot) . "\n";
    echo "Salvo na Public: " . realpath($outputPathPublic) . "\n";
} catch (\Exception $e) {
    echo "Erro ao gerar PDF: " . $e->getMessage() . "\n";
}
