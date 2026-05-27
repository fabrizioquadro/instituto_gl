<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- GERANDO RELATÓRIO HTML LIMPO E EXCLUSIVO (SEM MENÇÕES TÉCNICAS) ---\n";

$jsonPath = __DIR__ . '/relatorio_saldo_negativo.json';
if (!file_exists($jsonPath)) {
    die("Erro: Arquivo JSON de relatório não encontrado.\n");
}

$relatorio = json_decode(file_get_contents($jsonPath), true);

$html = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Auditoria de Estoque - Lotes com Saldo Negativo</title>
    <!-- Fonte Inter para Design Premium -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: "Inter", sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 40px 20px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            border: 1px solid #e2e8f0;
        }
        
        /* Cabeçalho do Cliente */
        .header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-title h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .header-title p {
            font-size: 13px;
            color: #64748b;
            margin: 5px 0 0 0;
        }
        .header-date {
            font-size: 12px;
            color: #64748b;
            text-align: right;
        }
        
        /* Seção por Unidade */
        .unidade-section {
            margin-bottom: 40px;
        }
        .unidade-title {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-left: 4px solid #0f172a;
        }
        
        /* Tabelas */
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-bottom: 10px;
        }
        th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #475569;
            background-color: #f8fafc;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            letter-spacing: 0.5px;
        }
        td {
            padding: 14px 16px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover td {
            background-color: #f8fafc;
        }
        
        /* Badges e Estilos Especiais */
        .lote-code {
            font-family: monospace;
            background-color: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: #0f172a;
        }
        .saldo-negativo {
            color: #ef4444;
            font-weight: 600;
        }
        .med-non-identified {
            color: #64748b;
            font-style: italic;
        }
        
        /* Rodapé do Relatório */
        .footer {
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        
        /* Estilos de Impressão */
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .container {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            .unidade-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <div class="header-title">
                <h1>Relatório de Lotes com Saldo Negativo</h1>
                <p>Auditoria de movimentações e conciliação de estoque por unidade</p>
            </div>
            <div class="header-date">
                <strong>Data de Emissão:</strong><br>
                ' . date('d/m/Y H:i') . '
            </div>
        </div>

        <!-- Tabelas das Unidades -->';

foreach ($relatorio as $clinica => $itens) {
    $html .= '
        <div class="unidade-section">
            <div class="unidade-title">🏢 ' . htmlspecialchars($clinica) . '</div>
            <table>
                <thead>
                    <tr>
                        <th width="40%">Medicamento</th>
                        <th width="20%">Fabricante</th>
                        <th width="15%">Lote</th>
                        <th width="10%" style="text-align: center;">Entradas</th>
                        <th width="10%" style="text-align: center;">Saídas</th>
                        <th width="15%" style="text-align: right;">Saldo</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($itens as $item) {
        $nomeMed = htmlspecialchars($item['medicamento']);
        $fab = htmlspecialchars($item['fabricante']);
        $lote = htmlspecialchars($item['lote']);
        
        if (is_null($item['medicamento_id'])) {
            $nomeMed = '<span class="med-non-identified">Medicamento não identificado</span>';
            $fab = '<span class="med-non-identified">Não Informado</span>';
        }
        
        $html .= '
                    <tr>
                        <td style="font-weight: 500;">' . $nomeMed . '</td>
                        <td>' . $fab . '</td>
                        <td><span class="lote-code">' . $lote . '</span></td>
                        <td style="text-align: center;">' . number_format($item['total_entrada'], 1, ',', '.') . '</td>
                        <td style="text-align: center;">' . number_format($item['total_saida'], 1, ',', '.') . '</td>
                        <td style="text-align: right;" class="saldo-negativo">-' . number_format(abs($item['saldo']), 1, ',', '.') . '</td>
                    </tr>';
    }

    $html .= '
                </tbody>
            </table>
        </div>';
}

$html .= '
        <!-- Rodapé -->
        <div class="footer">
            Este documento contém informações consolidadas do sistema de controle de estoque do Instituto GL.
        </div>
    </div>

</body>
</html>';

$outputPathRoot = __DIR__ . '/../relatorio_tabela.html';
$outputPathPublic = __DIR__ . '/../public/relatorio_tabela.html';

file_put_contents($outputPathRoot, $html);
file_put_contents($outputPathPublic, $html);

echo "HTML_GERADO_SUCESSO\n";
echo "Salvo na Raiz: " . realpath($outputPathRoot) . "\n";
echo "Salvo na Public: " . realpath($outputPathPublic) . "\n";
