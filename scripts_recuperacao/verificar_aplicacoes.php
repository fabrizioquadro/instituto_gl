<?php
/**
 * Script para verificar se os agendamentos da Feegow tiveram aplicação registrada no sistema local.
 * 
 * Lê um arquivo XLSX gerado pelo script agendamentos_feegow.php,
 * e para cada linha verifica se o paciente teve aplicação dos medicamentos na data indicada.
 * 
 * Uso: php scripts_recuperacao/verificar_aplicacoes.php [arquivo.xlsx]
 * 
 * Exemplos:
 *   php scripts_recuperacao/verificar_aplicacoes.php
 *   php scripts_recuperacao/verificar_aplicacoes.php scripts_recuperacao/agendamentos_feegow_17072026.xlsx
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Aplicacao;
use App\Models\Medicamento;
use PhpOffice\PhpSpreadsheet\IOFactory;

// ==============================================
// DEFINIR ARQUIVO
// ==============================================
$arquivo = $argv[1] ?? null;

if (!$arquivo) {
    // Listar XLSX disponíveis na pasta
    $xlsx_files = glob(__DIR__ . '/agendamentos_feegow_*.xlsx');
    if (empty($xlsx_files)) {
        die("Nenhum arquivo agendamentos_feegow_*.xlsx encontrado em scripts_recuperacao/.\n");
    }
    
    echo "Arquivos disponíveis:\n";
    foreach ($xlsx_files as $i => $f) {
        echo "  [$i] " . basename($f) . "\n";
    }
    echo "Digite o número do arquivo: ";
    $handle = fopen("php://stdin", "r");
    $idx = trim(fgets($handle));
    fclose($handle);
    
    if (!isset($xlsx_files[$idx])) {
        die("Opção inválida.\n");
    }
    $arquivo = $xlsx_files[$idx];
}

if (!file_exists($arquivo)) {
    die("Arquivo não encontrado: $arquivo\n");
}

echo "=============================================\n";
echo " VERIFICANDO APLICAÇÕES DOS AGENDAMENTOS\n";
echo "=============================================\n";
echo "Arquivo: " . basename($arquivo) . "\n\n";

// ==============================================
// LER XLSX
// ==============================================
$spreadsheet = IOFactory::load($arquivo);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

if (count($rows) < 2) {
    die("Arquivo vazio ou apenas com cabeçalho.\n");
}

// Pular cabeçalho (linha 1)
$cabecalho = $rows[0];
$dados = array_slice($rows, 1);

// Mapear índices das colunas
$idx_paciente_id = null;
$idx_data = null;
$idx_notas = null;
$idx_nome = null;

foreach ($cabecalho as $i => $col) {
    $col_upper = strtoupper(trim((string)$col));
    if (str_contains($col_upper, 'ID PACIENTE') || str_contains($col_upper, 'ID PACIENT')) {
        $idx_paciente_id = $i;
    } elseif ($col_upper === 'DATA' || str_contains($col_upper, 'DATA')) {
        $idx_data = $i;
    } elseif (str_contains($col_upper, 'NOTAS')) {
        $idx_notas = $i;
    } elseif (str_contains($col_upper, 'NOME DO PACIENTE') || str_contains($col_upper, 'NOME')) {
        $idx_nome = $i;
    }
}

if ($idx_paciente_id === null || $idx_data === null) {
    die("Colunas obrigatórias não encontradas no XLSX (ID Paciente, Data).\n");
}

echo "Colunas mapeadas:\n";
echo "  ID Paciente (coluna " . ($idx_paciente_id + 1) . ")\n";
echo "  Data (coluna " . ($idx_data + 1) . ")\n";
echo "  Notas (coluna " . ($idx_notas + 1) . ")\n";
echo "  Nome (coluna " . ($idx_nome + 1) . ")\n\n";

// ==============================================
// PROCESSAR CADA LINHA
// ==============================================
$resultados = [];
$total_linhas = count($dados);
$encontrados = 0;
$nao_encontrados = 0;
$erros = 0;

foreach ($dados as $i => $linha) {
    $paciente_id_feegow = trim((string)($linha[$idx_paciente_id] ?? ''));
    $data_xls = trim((string)($linha[$idx_data] ?? ''));
    $notas = trim((string)($linha[$idx_notas] ?? ''));
    $nome_paciente = trim((string)($linha[$idx_nome] ?? ''));
    $linha_num = $i + 2; // +2 porque linha 1 é cabeçalho e array é 0-indexed

    if (empty($paciente_id_feegow) || empty($data_xls)) {
        continue; // Pular linhas vazias
    }

    echo "  [$linha_num/$total_linhas] ID Feegow: $paciente_id_feegow, Data: $data_xls... ";

    // Converter data do XLS (dd-mm-aaaa) para yyyy-mm-dd (formato do banco)
    $partes_data = explode('-', $data_xls);
    $data_banco = null;
    if (count($partes_data) === 3) {
        $data_banco = $partes_data[2] . '-' . $partes_data[1] . '-' . $partes_data[0];
    }

    if (!$data_banco) {
        echo "ERRO: data inválida\n";
        $erros++;
        continue;
    }

    // Buscar paciente local
    $paciente = Paciente::where('paciente_id_feegow', $paciente_id_feegow)->first();

    if (!$paciente) {
        echo "PACIENTE NÃO ENCONTRADO no sistema local\n";
        $resultados[] = [
            'nome' => $nome_paciente ?: "ID Feegow: $paciente_id_feegow",
            'data' => $data_xls,
            'medicamentos' => $notas,
            'status' => 'Paciente não cadastrado',
            'encontrado' => false,
            'codigo' => '',
            'semana' => '',
            'procedimento_id' => '',
        ];
        $nao_encontrados++;
        continue;
    }

    // Buscar procedimentos do paciente na data
    $procedimentos = Procedimento::where('paciente_id', $paciente->id)
        ->where('data_aplicacao', $data_banco)
        ->orderBy('nr_procedimento')
        ->get();

    // Montar informações dos procedimentos (código, semana, id)
    $procedimentos_info = [];
    foreach ($procedimentos as $proc) {
        $procedimentos_info[] = [
            'codigo' => $proc->codigo,
            'semana' => $proc->nr_procedimento,
            'id' => $proc->id,
        ];
    }
    $codigos_str = implode(', ', array_column($procedimentos_info, 'codigo'));
    $semanas_str = implode(', ', array_column($procedimentos_info, 'semana'));
    $ids_str = implode(', ', array_column($procedimentos_info, 'id'));

    if ($procedimentos->isEmpty()) {
        echo "NENHUM PROCEDIMENTO encontrado para esta data\n";
        $resultados[] = [
            'nome' => $paciente->nm_paciente,
            'data' => $data_xls,
            'medicamentos' => $notas,
            'status' => 'Nenhum procedimento na data',
            'encontrado' => false,
            'codigo' => '',
            'semana' => '',
            'procedimento_id' => '',
        ];
        $nao_encontrados++;
        continue;
    }

    // Parsear medicamentos das Notas
    // Formato: "MedicamentoNome quantidade unidade(s), MedicamentoNome2 quantidade unidade(s)"
    $medicamentos_notas = [];
    if (!empty($notas)) {
        $partes_med = explode(',', $notas);
        foreach ($partes_med as $parte) {
            $parte = trim($parte);
            if (empty($parte)) continue;
            
            // Extrair nome do medicamento (tudo antes do primeiro número)
            if (preg_match('/^([^\d]+?)\s*[\d]/u', $parte, $matches)) {
                $medicamentos_notas[] = trim($matches[1]);
            } else {
                $medicamentos_notas[] = $parte;
            }
        }
    }

    // Buscar todas as aplicações dos procedimentos encontrados
    $proc_ids = $procedimentos->pluck('id')->toArray();
    $aplicacoes = Aplicacao::whereIn('procedimento_id', $proc_ids)->with('medicamento')->get();

    if ($aplicacoes->isEmpty()) {
        echo "PROCEDIMENTO(s) encontrado(s) mas SEM APLICAÇÕES registradas\n";
        $resultados[] = [
            'nome' => $paciente->nm_paciente,
            'data' => $data_xls,
            'medicamentos' => $notas,
            'status' => 'Procedimento sem aplicações',
            'encontrado' => false,
            'codigo' => $codigos_str,
            'semana' => $semanas_str,
            'procedimento_id' => $ids_str,
        ];
        $nao_encontrados++;
        continue;
    }

    // Verificar se os medicamentos das notas estão entre as aplicações
    $medicamentos_aplicados = [];
    foreach ($aplicacoes as $aplicacao) {
        $med_nome = $aplicacao->medicamento->nome ?? 'Medicamento #' . $aplicacao->medicamento_id;
        $medicamentos_aplicados[] = $med_nome . ' (' . $aplicacao->quantidade . ' ' . ($aplicacao->medicamento->unidade ?? 'un') . ')';
    }

    // Verificar correspondência
    $todos_encontrados = true;
    $med_encontrados = [];
    $med_faltando = [];

    if (!empty($medicamentos_notas)) {
        foreach ($medicamentos_notas as $med_nota) {
            $med_nota_lower = mb_strtolower($med_nota);
            $achou = false;
            foreach ($aplicacoes as $aplicacao) {
                $med_aplicado = mb_strtolower($aplicacao->medicamento->nome ?? '');
                // Verificar se o nome do medicamento da nota está contido no nome do medicamento aplicado
                if (!empty($med_aplicado) && 
                    (str_contains($med_aplicado, $med_nota_lower) || str_contains($med_nota_lower, $med_aplicado))) {
                    $achou = true;
                    break;
                }
            }
            if ($achou) {
                $med_encontrados[] = $med_nota;
            } else {
                $med_faltando[] = $med_nota;
                $todos_encontrados = false;
            }
        }
    }

    $medicamentos_aplicados_str = implode(', ', $medicamentos_aplicados);

    if ($todos_encontrados && !empty($medicamentos_notas)) {
        echo "APLICAÇÃO ENCONTRADA ✓\n";
        $resultados[] = [
            'nome' => $paciente->nm_paciente,
            'data' => $data_xls,
            'medicamentos' => $notas,
            'aplicacoes' => $medicamentos_aplicados_str,
            'status' => 'OK - Todos os medicamentos encontrados',
            'encontrado' => true,
            'codigo' => $codigos_str,
            'semana' => $semanas_str,
            'procedimento_id' => $ids_str,
        ];
        $encontrados++;
    } elseif (empty($medicamentos_notas)) {
        echo "APLICAÇÃO ENCONTRADA (sem medicamentos nas notas para comparar) ✓\n";
        $resultados[] = [
            'nome' => $paciente->nm_paciente,
            'data' => $data_xls,
            'medicamentos' => $notas,
            'aplicacoes' => $medicamentos_aplicados_str,
            'status' => 'OK - Sem medicamentos nas notas',
            'encontrado' => true,
            'codigo' => $codigos_str,
            'semana' => $semanas_str,
            'procedimento_id' => $ids_str,
        ];
        $encontrados++;
    } else {
        $faltantes = implode(', ', $med_faltando);
        echo "APLICAÇÃO PARCIAL: faltando: $faltantes\n";
        $resultados[] = [
            'nome' => $paciente->nm_paciente,
            'data' => $data_xls,
            'medicamentos' => $notas,
            'aplicacoes' => $medicamentos_aplicados_str,
            'status' => "Parcial - Faltando: $faltantes",
            'encontrado' => false,
            'codigo' => $codigos_str,
            'semana' => $semanas_str,
            'procedimento_id' => $ids_str,
        ];
        $nao_encontrados++;
    }
}

// ==============================================
// GERAR HTML
// ==============================================
echo "\nGerando HTML...\n";

$html_linhas = '';
foreach ($resultados as $r) {
    $status_class = $r['encontrado'] ? 'sucesso' : 'falha';
    $status_icon = $r['encontrado'] ? '✅' : '❌';
    $aplicacoes_html = htmlspecialchars($r['aplicacoes'] ?? '');
    $medicamentos_html = htmlspecialchars($r['medicamentos']);
    $nome_html = htmlspecialchars($r['nome']);
    $status_html = htmlspecialchars($r['status']);
    $codigo_html = htmlspecialchars($r['codigo'] ?? '');
    $semana_html = htmlspecialchars($r['semana'] ?? '');
    $procedimento_id_html = htmlspecialchars($r['procedimento_id'] ?? '');
    
    $html_linhas .= <<<HTML
        <tr class="{$status_class}">
            <td>{$status_icon}</td>
            <td>{$r['data']}</td>
            <td>{$nome_html}</td>
            <td>{$codigo_html}</td>
            <td>{$semana_html}</td>
            <td>{$procedimento_id_html}</td>
            <td>{$medicamentos_html}</td>
            <td>{$aplicacoes_html}</td>
            <td>{$status_html}</td>
        </tr>
HTML;
}

$nome_arquivo_html = basename($arquivo);
$data_geracao = date('d/m/Y H:i:s');
$data_geracao2 = date('d/m/Y H:i:s');

$html = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Aplicações - Agendamentos Feegow</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 10px; }
        .info { color: #666; margin-bottom: 20px; font-size: 14px; }
        .resumo {
            display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .card {
            background: white; border-radius: 8px; padding: 15px 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; flex: 1; min-width: 150px;
        }
        .card .numero { font-size: 28px; font-weight: bold; }
        .card .rotulo { font-size: 12px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .card.total .numero { color: #333; }
        .card.ok .numero { color: #28a745; }
        .card.falha .numero { color: #dc3545; }
        .card.erro .numero { color: #ffc107; }
        table {
            width: 100%; border-collapse: collapse; background: white;
            border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background: #4472C4; color: white; padding: 12px 10px;
            text-align: left; font-size: 13px; white-space: nowrap;
        }
        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; }
        tr:hover { background: #f8f9fa; }
        tr.sucesso td:first-child { border-left: 4px solid #28a745; }
        tr.falha td:first-child { border-left: 4px solid #dc3545; }
        .footer { margin-top: 20px; text-align: center; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Verificação de Aplicações</h1>
        <div class="info">Arquivo: {$nome_arquivo_html} | Gerado em: {$data_geracao}</div>
        
        <div class="resumo">
            <div class="card total">
                <div class="numero">{$total_linhas}</div>
                <div class="rotulo">Total de Agendamentos</div>
            </div>
            <div class="card ok">
                <div class="numero">{$encontrados}</div>
                <div class="rotulo">Com Aplicação ✓</div>
            </div>
            <div class="card falha">
                <div class="numero">{$nao_encontrados}</div>
                <div class="rotulo">Sem Aplicação ❌</div>
            </div>
            <div class="card erro">
                <div class="numero">{$erros}</div>
                <div class="rotulo">Erros</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:30px"></th>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>Código</th>
                    <th>Semana</th>
                    <th>ID Procedimento</th>
                    <th>Medicamentos (Feegow)</th>
                    <th>Aplicações (Sistema)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                {$html_linhas}
            </tbody>
        </table>
        
        <div class="footer">
            Script: verificar_aplicacoes.php | {$data_geracao2}
        </div>
    </div>
</body>
</html>
HTML;

// Salvar HTML
$nome_base = pathinfo($arquivo, PATHINFO_FILENAME);
$nome_html = $nome_base . '_verificacao.html';
$caminho_html = __DIR__ . '/' . $nome_html;
file_put_contents($caminho_html, $html);

echo "\n=============================================\n";
echo " VERIFICAÇÃO CONCLUÍDA!\n";
echo "=============================================\n";
echo "Total de agendamentos:  $total_linhas\n";
echo "Com aplicação:          $encontrados ✅\n";
echo "Sem aplicação:          $nao_encontrados ❌\n";
echo "Erros:                  $erros\n";
echo "---------------------------------------------\n";
echo "HTML gerado: $caminho_html\n";
echo "=============================================\n";
