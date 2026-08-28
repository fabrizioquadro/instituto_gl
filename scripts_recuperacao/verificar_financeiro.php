<?php
/**
 * Script de VERIFICAÇÃO / AUDITORIA do relatório financeiro
 * 
 * Lê o arquivo Financeiro_*.xlsx gerado pelo relatório "exportar_financeiro"
 * e verifica linha por linha:
 * 
 * 1) A coluna A contém o id da forma de pagamento (financeiro_formas_pagamentos.id).
 *    - Se o id NÃO existir no BD => a linha precisa ser inserida depois (marcada)
 *    - Se existir => vai para a análise 2
 * 
 * 2) Compara os dados da linha com o BD (paciente, valores, forma, rateio, clínica, médico, etc.)
 * 
 * Saída: HTML com uma tabela detalhando o que foi encontrado em cada linha.
 * 
 * Uso: php scripts_recuperacao/verificar_financeiro.php [arquivo.xlsx]
 * Exemplo: php scripts_recuperacao/verificar_financeiro.php scripts_recuperacao/Financeiro_20260722194848.xlsx
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FinanceiroFormasPagamento;
use App\Models\Financeiro;
use App\Models\Paciente;
use App\Models\Procedimento;
use PhpOffice\PhpSpreadsheet\IOFactory;

// ==============================================
// HELPERS DE NORMALIZAÇÃO
// ==============================================
function normaliza_valor($str){
    $str = trim((string)$str);
    if ($str === '' || $str === null) return null;
    // Remove 'R$ '
    $str = str_replace('R$ ', '', $str);
    $str = str_replace('R$', '', $str);
    $str = trim($str);
    // Formato brasileiro: 1.990,00 ou 0,00
    $negativo = false;
    if (str_starts_with($str, '-')) { $negativo = true; $str = substr($str, 1); }
    $str = str_replace('.', '', $str);   // remove separador de milhar
    $str = str_replace(',', '.', $str);  // vírgula decimal -> ponto
    $val = (float)$str;
    return $negativo ? -$val : $val;
}

function normaliza_data($str){
    $str = trim((string)$str);
    // dd/mm/yyyy -> yyyy-mm-dd
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $str, $m)) {
        return $m[3].'-'.$m[2].'-'.$m[1];
    }
    return $str;
}

function fmt_valor($val){
    if ($val === null || $val === '') return '';
    return 'R$ '.number_format((float)$val, 2, ',', '.');
}

function cmp_valor($xls_val, $db_val){
    $x = normaliza_valor($xls_val);
    $d = $db_val === null || $db_val === '' ? null : (float)$db_val;
    if ($x === null && $d === null) return ['ok' => true, 'xls' => $xls_val, 'db' => $d];
    if ($x === null || $d === null) return ['ok' => false, 'xls' => $xls_val, 'db' => $d];
    $ok = abs($x - $d) < 0.005;
    return ['ok' => $ok, 'xls' => $xls_val, 'db' => $d];
}

// ==============================================
// DATA DE CORTE PARA A ÚLTIMA ALTERAÇÃO
// ==============================================
// Registros alterados ANTES desta data => VERDE (íntegros)
// Registros alterados NESTA data em diante => VERMELHO (suspeitos/afetados pelo erro)
$data_corte = '2026-07-16';

// Gera o badge colorido da última alteração
function badge_alteracao($data_str, $data_corte){
    if (empty($data_str)) {
        return '<span class="badge badge-none">sem data</span>';
    }
    // O formato é d/m/Y H:i
    $dt = DateTime::createFromFormat('d/m/Y H:i', $data_str);
    if (!$dt) {
        return '<span class="badge badge-none">' . htmlspecialchars($data_str) . '</span>';
    }
    $corte_ts = strtotime($data_corte . ' 00:00:00');
    if ($dt->getTimestamp() < $corte_ts) {
        return '<span class="badge badge-ok" title="Alterado antes de ' . $data_corte . '">' . htmlspecialchars($data_str) . '</span>';
    }
    return '<span class="badge badge-alert" title="Alterado a partir de ' . $data_corte . '">' . htmlspecialchars($data_str) . '</span>';
}

// Busca todos os pagamentos (formas) de um paciente, em ordem crescente de data
function get_pagamentos_paciente($paciente_id){
    $financeiros = \App\Models\Financeiro::where('paciente_id', $paciente_id)
        ->orderBy('dt_pagamento')
        ->get();

    $pagamentos = [];
    foreach ($financeiros as $fin) {
        $formas = \App\Models\FinanceiroFormasPagamento::where('financeiro_id', $fin->id)
            ->orderBy('created_at')
            ->get();
        foreach ($formas as $forma) {
            $pagamentos[] = [
                'forma_id' => $forma->id,
                'financeiro_id' => $fin->id,
                'dt_pagamento' => $fin->dt_pagamento ? date('d/m/Y', strtotime($fin->dt_pagamento)) : '',
                'data_forma' => $forma->created_at ? date('d/m/Y H:i', strtotime($forma->created_at)) : '',
                'created_at_raw' => $forma->created_at ?? '',
                'forma_pagamento' => $forma->forma_pagamento,
                'parcelas' => $forma->parcelas,
                'vl_pagamento' => $forma->vl_pagamento,
                'id_pagamento' => $forma->id_pagamento,
                'tipo' => $fin->tipo_pagamento,
                'vl_total' => $fin->vl_pagamento,
                'updated_at' => $forma->updated_at ? date('d/m/Y H:i', strtotime($forma->updated_at)) : '',
            ];
        }
    }

    // Ordena por data da forma (created_at) crescente
    usort($pagamentos, function($a, $b){
        return strcmp((string)$a['created_at_raw'], (string)$b['created_at_raw']);
    });

    return $pagamentos;
}

// Localiza o paciente a partir dos dados da LINHA do XLSX (ID Feegow ou CPF)
function get_paciente_da_linha($id_feegow, $cpf){
    if ($id_feegow !== '') {
        $p = \App\Models\Paciente::where('paciente_id_feegow', $id_feegow)->first();
        if ($p) return $p;
    }
    if ($cpf !== '') {
        return \App\Models\Paciente::where('cpf', $cpf)->first();
    }
    return null;
}

// ==============================================
// DEFINIR ARQUIVO
// ==============================================
$arquivo = $argv[1] ?? null;

if (!$arquivo) {
    $xlsx_files = glob(__DIR__ . '/Financeiro_*.xlsx');
    if (empty($xlsx_files)) {
        die("Nenhum arquivo Financeiro_*.xlsx encontrado em scripts_recuperacao/.\n");
    }
    echo "Arquivos disponíveis:\n";
    foreach ($xlsx_files as $i => $f) {
        echo "  [$i] " . basename($f) . "\n";
    }
    echo "Digite o número do arquivo: ";
    $handle = fopen("php://stdin", "r");
    $idx = trim(fgets($handle));
    fclose($handle);
    if (!isset($xlsx_files[$idx])) die("Opção inválida.\n");
    $arquivo = $xlsx_files[$idx];
}

if (!file_exists($arquivo)) {
    die("Arquivo não encontrado: $arquivo\n");
}

echo "=============================================\n";
echo " VERIFICAÇÃO DO RELATÓRIO FINANCEIRO\n";
echo "=============================================\n";
echo "Arquivo: " . basename($arquivo) . "\n\n";

// ==============================================
// LER XLSX
// ==============================================
echo "Lendo arquivo XLSX...\n";

$reader = IOFactory::createReaderForFile($arquivo);
$reader->setReadDataOnly(true); // ignora estilos (mais rápido)
$spreadsheet = $reader->load($arquivo);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true); // chaves = letras das colunas

if (count($rows) < 2) {
    die("Arquivo vazio ou apenas com cabeçalho.\n");
}

$cabecalho = $rows[1]; // Linha 1 do Excel = cabeçalho (toArray retorna chaves 1-based)
$dados = array_slice($rows, 1, null, true); // dados começam na posição 1 = Linha 2 do Excel

echo "Total de linhas de dados: " . count($dados) . "\n\n";

// ==============================================
// PROCESSAR CADA LINHA
// ==============================================
$resultados = [];
$count_nao_existe = 0;
$count_existe = 0;
$count_tudo_ok = 0;
$count_divergencias = 0;
$count_erros = 0;
$total = count($dados);
$contador = 0;

// Caches para acelerar (get_rateio_financeiro é caro e as mesmas formas se repetem)
$rateio_cache = [];
$financeiro_cache = [];
$procedimento_cache = [];
$contador_proc_cache = [];
$paciente_nome_cache = [];
$paciente_feegow_cache = [];
$paciente_cpf_cache = [];
$clinica_nome_cache = [];
$pagamentos_cache = [];

foreach ($dados as $numLinha => $linha) {
    $contador++;
    $forma_id = trim((string)($linha['A'] ?? ''));

    // Pular linhas totalmente vazias
    if ($forma_id === '' && trim((string)($linha['B'] ?? '')) === '') {
        continue;
    }

    echo "  [$contador/$total] Linha $numLinha | ID forma: $forma_id ... ";

    $resultado = [
        'linha' => $numLinha,
        'forma_id' => $forma_id,
        'data' => $linha['B'] ?? '',
        'paciente' => $linha['C'] ?? '',
        'id_feegow' => $linha['D'] ?? '',
        'cpf' => $linha['E'] ?? '',
        'codigo' => $linha['F'] ?? '',
        'tipo' => $linha['K'] ?? '',
        'obs' => $linha['S'] ?? '',
        'status' => '',
        'detalhes' => [],
        'ultima_alteracao' => '',
        'ultima_alteracao_fin' => '',
        'pagamentos' => [],
        'paciente_local_id' => null,
    ];

    // ============ ANÁLISE 1: a forma de pagamento existe? ============
    $forma = FinanceiroFormasPagamento::find($forma_id);

    if (!$forma) {
        $resultado['status'] = 'NÃO EXISTE no BD';
        $resultado['detalhes'][] = [
            'campo' => 'Forma de Pagamento (col A)',
            'xls' => $forma_id,
            'db' => '(não encontrada)',
            'ok' => false,
            'obs' => 'Esta linha precisa ser INSERIDA posteriormente',
        ];

        // Tentar identificar o paciente (por ID Feegow ou CPF) para mostrar os pagamentos dele
        $id_feegow = trim((string)($linha['D'] ?? ''));
        $cpf_xls = trim((string)($linha['E'] ?? ''));
        $paciente_faltante = get_paciente_da_linha($id_feegow, $cpf_xls);
        if ($paciente_faltante) {
            $resultado['paciente_local_id'] = $paciente_faltante->id;
            if (!isset($pagamentos_cache[$paciente_faltante->id])) {
                $pagamentos_cache[$paciente_faltante->id] = get_pagamentos_paciente($paciente_faltante->id);
            }
            $resultado['pagamentos'] = $pagamentos_cache[$paciente_faltante->id];
        }

        $resultados[] = $resultado;
        $count_nao_existe++;
        echo "NÃO EXISTE ❌ (inserir depois)\n";
        continue;
    }

    // ============ ANÁLISE 2: comparar com o BD ============
    $count_existe++;
    $resultado['ultima_alteracao'] = $forma->updated_at ? date('d/m/Y H:i', strtotime($forma->updated_at)) : '';
    $financeiro = $financeiro_cache[$forma->financeiro_id] ?? null;
    if (!$financeiro) {
        $financeiro = Financeiro::find($forma->financeiro_id);
        $financeiro_cache[$forma->financeiro_id] = $financeiro;
    }
    if ($financeiro && $financeiro->updated_at) {
        $resultado['ultima_alteracao_fin'] = date('d/m/Y H:i', strtotime($financeiro->updated_at));
    }

    // Preencher os pagamentos do PACIENTE DA LINHA DO XLSX (por ID Feegow/CPF),
    // não o paciente do BD (que pode estar divergente, como no caso da forma 9787)
    $id_feegow = trim((string)($linha['D'] ?? ''));
    $cpf_xls = trim((string)($linha['E'] ?? ''));
    $paciente_xlsx = get_paciente_da_linha($id_feegow, $cpf_xls);
    if ($paciente_xlsx) {
        $resultado['paciente_local_id'] = $paciente_xlsx->id;
        if (!isset($pagamentos_cache[$paciente_xlsx->id])) {
            $pagamentos_cache[$paciente_xlsx->id] = get_pagamentos_paciente($paciente_xlsx->id);
        }
        $resultado['pagamentos'] = $pagamentos_cache[$paciente_xlsx->id];
    }

    $ok_geral = true;

    if (!$financeiro) {
        $resultado['status'] = 'ERRO: forma existe mas financeiro não';
        $resultado['detalhes'][] = [
            'campo' => 'Financeiro (financeiro_id)',
            'xls' => '',
            'db' => '(financeiro_id=' . $forma->financeiro_id . ' não encontrado)',
            'ok' => false,
            'obs' => 'Verificar integridade',
        ];
        $resultados[] = $resultado;
        $count_erros++;
        echo "ERRO financeiro ausente\n";
        continue;
    }

    // --- Comparações básicas ---
    $cmp = [];

    $financeiro_id = $forma->financeiro_id;

    // Paciente (col C), ID Feegow (col D), CPF (col E) com cache
    if (!isset($paciente_nome_cache[$financeiro_id])) {
        $paciente = $financeiro->paciente;
        $clinica = $financeiro->clinica;
        $paciente_nome_cache[$financeiro_id] = $paciente ? $paciente->nm_paciente : '(sem paciente)';
        $paciente_feegow_cache[$financeiro_id] = $paciente ? $paciente->paciente_id_feegow : '';
        $paciente_cpf_cache[$financeiro_id] = $paciente ? $paciente->cpf : '';
        $clinica_nome_cache[$financeiro_id] = $clinica ? $clinica->nome : '(sem clínica)';
    }
    $db_pac = $paciente_nome_cache[$financeiro_id];
    $db_feegow = $paciente_feegow_cache[$financeiro_id];
    $db_cpf = $paciente_cpf_cache[$financeiro_id];
    $db_clinica = $clinica_nome_cache[$financeiro_id];

    // Data (col B) vs created_at da forma
    $db_data = substr($forma->created_at ?? '', 0, 10);
    $db_data_xls = $db_data ? date('d/m/Y', strtotime($db_data)) : '';
    $c = ($linha['B'] ?? '') == $db_data_xls;
    $cmp[] = ['campo' => 'Data', 'xls' => $linha['B'] ?? '', 'db' => $db_data_xls, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // Paciente (col C)
    $c = trim((string)($linha['C'] ?? '')) == $db_pac;
    $cmp[] = ['campo' => 'Paciente', 'xls' => $linha['C'] ?? '', 'db' => $db_pac, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // ID Feegow (col D)
    $c = trim((string)($linha['D'] ?? '')) == (string)$db_feegow;
    $cmp[] = ['campo' => 'ID Feegow', 'xls' => $linha['D'] ?? '', 'db' => $db_feegow, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // CPF (col E)
    $c = trim((string)($linha['E'] ?? '')) == trim((string)$db_cpf);
    $cmp[] = ['campo' => 'CPF', 'xls' => $linha['E'] ?? '', 'db' => $db_cpf, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // Valor Tratamento (col G)
    $c = cmp_valor($linha['G'] ?? '', $financeiro->vl_procedimentos);
    $cmp[] = ['campo' => 'Valor Tratamento', 'xls' => $c['xls'], 'db' => fmt_valor($c['db']), 'ok' => $c['ok'], 'obs' => ''];
    if (!$c['ok']) $ok_geral = false;

    // Desconto Total (col H)
    $c = cmp_valor($linha['H'] ?? '', $financeiro->vl_desconto);
    $cmp[] = ['campo' => 'Desconto Total', 'xls' => $c['xls'], 'db' => fmt_valor($c['db']), 'ok' => $c['ok'], 'obs' => ''];
    if (!$c['ok']) $ok_geral = false;

    // Pagamento (col I) = vl_pagamento da forma
    $c = cmp_valor($linha['I'] ?? '', $forma->vl_pagamento);
    $cmp[] = ['campo' => 'Pagamento', 'xls' => $c['xls'], 'db' => fmt_valor($c['db']), 'ok' => $c['ok'], 'obs' => ''];
    if (!$c['ok']) $ok_geral = false;

    // Forma Pagamento (col M)
    $db_fp = $forma->forma_pagamento;
    $c = trim((string)($linha['M'] ?? '')) == trim((string)$db_fp);
    $cmp[] = ['campo' => 'Forma Pagamento', 'xls' => $linha['M'] ?? '', 'db' => $db_fp, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // ID Pagamento (col N)
    $db_idpag = $forma->id_pagamento;
    $c = trim((string)($linha['N'] ?? '')) == trim((string)$db_idpag);
    $cmp[] = ['campo' => 'ID Pagamento', 'xls' => $linha['N'] ?? '', 'db' => $db_idpag, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // Parcelas (col O)
    $db_parc = $forma->parcelas;
    $c = trim((string)($linha['O'] ?? '')) == trim((string)$db_parc);
    $cmp[] = ['campo' => 'Parcelas', 'xls' => $linha['O'] ?? '', 'db' => $db_parc, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // Clinica (col P)
    $c = trim((string)($linha['P'] ?? '')) == $db_clinica;
    $cmp[] = ['campo' => 'Clínica', 'xls' => $linha['P'] ?? '', 'db' => $db_clinica, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // Médico (col Q)
    $db_medico = $financeiro->medico;
    $c = trim((string)($linha['Q'] ?? '')) == trim((string)$db_medico);
    $cmp[] = ['campo' => 'Médico', 'xls' => $linha['Q'] ?? '', 'db' => $db_medico, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // Obs (col S)
    $db_obs = $financeiro->obs_pagamento;
    $c = trim((string)($linha['S'] ?? '')) == trim((string)$db_obs);
    $cmp[] = ['campo' => 'Obs', 'xls' => $linha['S'] ?? '', 'db' => $db_obs, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // Codigo (col F): procedimento do financeiro (com cache por financeiro)
    if (!array_key_exists($financeiro_id, $procedimento_cache)) {
        $procedimento_cache[$financeiro_id] = $financeiro->procedimentos()->first();
    }
    $proc = $procedimento_cache[$financeiro_id];
    $db_codigo = $proc ? $proc->codigo : '';
    $c = trim((string)($linha['F'] ?? '')) == trim((string)$db_codigo);
    $cmp[] = ['campo' => 'Código Proc.', 'xls' => $linha['F'] ?? '', 'db' => $db_codigo, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // Nr Procedimentos (col R) (com cache por codigo)
    if (!array_key_exists($financeiro_id, $contador_proc_cache)) {
        $contador_proc_cache[$financeiro_id] = $proc ? Procedimento::where('codigo', $proc->codigo)->count() : 0;
    }
    $contador_proc = $contador_proc_cache[$financeiro_id];
    $c = trim((string)($linha['R'] ?? '')) == (string)$contador_proc;
    $cmp[] = ['campo' => 'Nr Procedimentos', 'xls' => $linha['R'] ?? '', 'db' => $contador_proc, 'ok' => $c, 'obs' => ''];
    if (!$c) $ok_geral = false;

    // --- Rateio (col J e L) com base no Tipo (col K) ---
    $tipo_xls = trim((string)($linha['K'] ?? ''));
    if (!isset($rateio_cache[$forma_id])) {
        $rateio_cache[$forma_id] = $forma->get_rateio_financeiro();
    }
    $rateio = $rateio_cache[$forma_id];
    $vl_rateio_esperado = null;
    $desconto_rateio_esperado = null;
    $obs_rateio = '';

    if (str_starts_with($tipo_xls, 'Consulta')) {
        $vl_rateio_esperado = $rateio['vl_consulta'];
        $desconto_rateio_esperado = 0.0;
    } elseif (str_starts_with($tipo_xls, 'Aplicação')) {
        $vl_rateio_esperado = $rateio['vl_aplicacao'];
        $desconto_rateio_esperado = floatval($financeiro->vl_desconto);
    } elseif (str_starts_with($tipo_xls, 'Procedimento (')) {
        // Extrair nome do procedimento entre parênteses
        if (preg_match('/\((.+)\)/', $tipo_xls, $m)) {
            $nome_proc = $m[1];
            $achou = false;
            foreach ($rateio['detalhes_procedimentos'] as $dp) {
                if ($dp['nome'] == $nome_proc) {
                    $vl_rateio_esperado = $dp['valor'];
                    $achou = true;
                    break;
                }
            }
            if (!$achou) $obs_rateio = "detalhe '$nome_proc' não encontrado no rateio";
        }
        $desconto_rateio_esperado = floatval($financeiro->vl_desconto);
    } elseif ($tipo_xls == 'Procedimento') {
        $vl_rateio_esperado = $rateio['vl_procedimento'];
        $desconto_rateio_esperado = floatval($financeiro->vl_desconto);
    } else {
        $obs_rateio = "tipo '$tipo_xls' não reconhecido";
    }

    // Valor Rateio (col J)
    if ($vl_rateio_esperado !== null) {
        $c = cmp_valor($linha['J'] ?? '', $vl_rateio_esperado);
        $cmp[] = ['campo' => 'Valor Rateio', 'xls' => $c['xls'], 'db' => fmt_valor($c['db']), 'ok' => $c['ok'], 'obs' => $obs_rateio];
        if (!$c['ok']) $ok_geral = false;
    }

    // Desconto Rateio (col L)
    if ($desconto_rateio_esperado !== null) {
        $c = cmp_valor($linha['L'] ?? '', $desconto_rateio_esperado);
        $cmp[] = ['campo' => 'Desconto Rateio', 'xls' => $c['xls'], 'db' => fmt_valor($c['db']), 'ok' => $c['ok'], 'obs' => ''];
        if (!$c['ok']) $ok_geral = false;
    }

    // Tipo (col K)
    $c = trim((string)($linha['K'] ?? '')) == $tipo_xls; // o tipo da linha do XLS é o que determina a comparação
    $cmp[] = ['campo' => 'Tipo', 'xls' => $tipo_xls, 'db' => $tipo_xls, 'ok' => true, 'obs' => ''];

    // Montar resultado
    $resultado['detalhes'] = $cmp;
    if ($ok_geral) {
        $resultado['status'] = 'OK - todos os dados batem';
        $count_tudo_ok++;
        echo "OK ✓\n";
    } else {
        $divergentes = 0;
        foreach ($cmp as $c) if (!$c['ok']) $divergentes++;
        $resultado['status'] = "DIVERGÊNCIAS ($divergentes campo(s))";
        $count_divergencias++;
        echo "DIVERGÊNCIAS ❌\n";
    }

    $resultados[] = $resultado;
}

// ==============================================
// GERAR HTML
// ==============================================
echo "\nGerando HTML...\n";

$html_linhas = '';
foreach ($resultados as $r) {
    $status_class = 'info';
    if ($r['status'] === 'OK - todos os dados batem') $status_class = 'sucesso';
    elseif (str_starts_with($r['status'], 'DIVERGÊNCIAS')) $status_class = 'divergencia';
    elseif ($r['status'] === 'NÃO EXISTE no BD') $status_class = 'faltando';
    elseif (str_starts_with($r['status'], 'ERRO')) $status_class = 'erro';

    $status_icon = $status_class === 'sucesso' ? '✅' : ($status_class === 'divergencia' ? '⚠️' : ($status_class === 'faltando' ? '❌' : '🚨'));

    // Montar sub-tabela de detalhes
    $det_html = '<table class="det">';
    foreach ($r['detalhes'] as $d) {
        $det_class = $d['ok'] ? 'ok' : 'diff';
        $icone = $d['ok'] ? '✓' : '✗';
        $obs_d = !empty($d['obs']) ? '<span class="obs">(' . htmlspecialchars($d['obs']) . ')</span>' : '';
        $det_html .= '<tr class="' . $det_class . '"><td class="icone">' . $icone . '</td><td class="campo">' . htmlspecialchars($d['campo']) . '</td><td class="xls">' . htmlspecialchars((string)$d['xls']) . '</td><td class="seta">→</td><td class="db">' . htmlspecialchars((string)$d['db']) . '</td><td>' . $obs_d . '</td></tr>';
    }
    $det_html .= '</table>';

    // --- Quadro de pagamentos do paciente (ordem crescente) ---
    $pag_html = '';
    if (!empty($r['pagamentos'])) {
        $pag_html .= '<div class="pag-box">';
        $pag_html .= '<div class="pag-titulo">💳 Pagamentos do paciente (' . count($r['pagamentos']) . '):</div>';
        $pag_html .= '<table class="det pag">';
        $pag_html .= '<tr class="pag-head"><td>ID Forma</td><td>ID Finan.</td><td>Data Forma</td><td>Forma</td><td>Parc.</td><td>Valor</td><td>ID Pag.</td><td>Últ. Alteração</td></tr>';
        foreach ($r['pagamentos'] as $p) {
            $pag_html .= '<tr>';
            $pag_html .= '<td class="mono">' . $p['forma_id'] . '</td>';
            $pag_html .= '<td class="mono">' . $p['financeiro_id'] . '</td>';
            $pag_html .= '<td>' . $p['data_forma'] . '</td>';
            $pag_html .= '<td>' . htmlspecialchars((string)$p['forma_pagamento']) . '</td>';
            $pag_html .= '<td>' . $p['parcelas'] . '</td>';
            $pag_html .= '<td>' . fmt_valor($p['vl_pagamento']) . '</td>';
            $pag_html .= '<td class="mono">' . htmlspecialchars((string)$p['id_pagamento']) . '</td>';
            $pag_html .= '<td>' . $p['updated_at'] . '</td>';
            $pag_html .= '</tr>';
        }
        $pag_html .= '</table>';
        $pag_html .= '</div>';
    } else {
        $pag_html = '<div class="pag-box"><div class="pag-titulo pag-vazio">💳 Paciente sem pagamentos registrados ou não localizado</div></div>';
    }

    $detalhe_composto = $det_html . $pag_html;

    $paciente_html = htmlspecialchars($r['paciente']);

    $badge_forma = badge_alteracao($r['ultima_alteracao'], $data_corte);
    $badge_fin = badge_alteracao($r['ultima_alteracao_fin'], $data_corte);

    $html_linhas .= <<<HTML
    <tr class="{$status_class}">
        <td>{$status_icon}</td>
        <td class="mono">{$r['linha']}</td>
        <td class="mono">{$r['forma_id']}</td>
        <td>{$r['data']}</td>
        <td>{$paciente_html}</td>
        <td class="mono">{$r['id_feegow']}</td>
        <td>{$r['tipo']}</td>
        <td>{$badge_forma}</td>
        <td>{$badge_fin}</td>
        <td class="status-text">{$r['status']}</td>
        <td>{$detalhe_composto}</td>
    </tr>
HTML;
}

$nome_base = pathinfo($arquivo, PATHINFO_FILENAME);
$nome_html = $nome_base . '_verificacao.html';
$caminho_html = __DIR__ . '/' . $nome_html;
$nome_arquivo_html = basename($arquivo);
$data_geracao = date('d/m/Y H:i:s');

$html = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação Financeiro - {nome_arquivo_html}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f5f7; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 8px; font-size: 22px; }
        .info { color: #666; margin-bottom: 18px; font-size: 13px; }
        .resumo { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .card {
            background: white; border-radius: 8px; padding: 14px 22px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08); text-align: center; flex: 1; min-width: 140px;
        }
        .card .numero { font-size: 26px; font-weight: 700; }
        .card .rotulo { font-size: 11px; color: #777; text-transform: uppercase; margin-top: 4px; }
        .card.total .numero { color: #333; }
        .card.ok .numero { color: #28a745; }
        .card.div .numero { color: #e67e22; }
        .card.falt .numero { color: #dc3545; }
        .card.err .numero { color: #8e44ad; }
        table {
            width: 100%; border-collapse: collapse; background: white;
            border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            font-size: 12px;
        }
        th {
            background: #34495e; color: white; padding: 10px 8px;
            text-align: left; white-space: nowrap; font-size: 12px;
        }
        td { padding: 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:hover { background: #f8f9fa; }
        tr.sucesso td:first-child { border-left: 4px solid #28a745; }
        tr.divergencia td:first-child { border-left: 4px solid #e67e22; }
        tr.faltando td:first-child { border-left: 4px solid #dc3545; }
        tr.erro td:first-child { border-left: 4px solid #8e44ad; }
        tr.info td:first-child { border-left: 4px solid #95a5a6; }
        .mono { font-family: Consolas, monospace; }
        .status-text { font-weight: 600; }
        tr.sucesso .status-text { color: #28a745; }
        tr.divergencia .status-text { color: #e67e22; }
        tr.faltando .status-text { color: #dc3545; }
        tr.erro .status-text { color: #8e44ad; }
        table.det {
            width: 100%; font-size: 11px; background: #fafafa;
            box-shadow: none; border: 1px solid #e5e5e5; margin-top: 2px;
        }
        table.det td { padding: 4px 6px; border-bottom: 1px solid #f0f0f0; }
        table.det tr.ok td { background: #f0fff4; }
        table.det tr.diff td { background: #fff5f5; }
        table.det td.icone { width: 20px; text-align: center; font-weight: 700; }
        table.det tr.ok td.icone { color: #28a745; }
        table.det tr.diff td.icone { color: #dc3545; }
        table.det td.campo { font-weight: 600; width: 130px; white-space: nowrap; }
        table.det td.xls { color: #333; }
        table.det td.seta { color: #999; width: 20px; }
        table.det td.db { color: #555; }
        .obs { color: #e67e22; font-style: italic; }
        .pag-box { margin-top: 8px; }
        .pag-titulo { font-size: 11px; font-weight: 700; color: #34495e; margin-bottom: 4px; }
        .pag-vazio { color: #999; font-weight: 500; }
        table.det.pag tr.pag-head td { background: #eef2f7; font-weight: 700; color: #34495e; white-space: nowrap; }
        table.det.pag td { white-space: nowrap; }
        table.det.pag tr:hover td { background: #f0f4ff; }
        .badge {
            display: inline-block; padding: 3px 8px; border-radius: 12px;
            font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        .badge-ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-alert { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .badge-none { background: #e9ecef; color: #6c757d; border: 1px solid #ced4da; }
        .legenda { margin-bottom: 14px; font-size: 12px; color: #555; }
        .legenda .badge { margin-right: 10px; }
        .footer { margin-top: 18px; text-align: center; color: #aaa; font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Verificação do Relatório Financeiro</h1>
        <div class="info">Arquivo: {$nome_arquivo_html} | Gerado em: {$data_geracao}</div>

        <div class="legenda">
            Última alteração (updated_at) comparada com a data de corte <b>{$data_corte}</b>:
            <span class="badge badge-ok">Verde = alterado antes de {$data_corte}</span>
            <span class="badge badge-alert">Vermelho = alterado a partir de {$data_corte}</span>
            <span class="badge badge-none">Cinza = sem data / não existe</span>
        </div>

        <div class="resumo">
            <div class="card total"><div class="numero">{$total}</div><div class="rotulo">Total de Linhas</div></div>
            <div class="card ok"><div class="numero">{$count_tudo_ok}</div><div class="rotulo">OK ✓</div></div>
            <div class="card div"><div class="numero">{$count_divergencias}</div><div class="rotulo">Divergências ⚠️</div></div>
            <div class="card falt"><div class="numero">{$count_nao_existe}</div><div class="rotulo">Não Existem ❌</div></div>
            <div class="card err"><div class="numero">{$count_erros}</div><div class="rotulo">Erros 🚨</div></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:28px"></th>
                    <th>Linha</th>
                    <th>ID Forma</th>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>ID Feegow</th>
                    <th>Tipo</th>
                    <th>Últ. Alteração (Forma)</th>
                    <th>Últ. Alteração (Financeiro)</th>
                    <th>Status</th>
                    <th>Detalhes (campo | XLS | BD)</th>
                </tr>
            </thead>
            <tbody>
                {$html_linhas}
            </tbody>
        </table>

        <div class="footer">Script: verificar_financeiro.php | {$data_geracao}</div>
    </div>
</body>
</html>
HTML;

file_put_contents($caminho_html, $html);

echo "\n=============================================\n";
echo " VERIFICAÇÃO CONCLUÍDA!\n";
echo "=============================================\n";
echo "Total de linhas:        $total\n";
echo "OK (tudo bate):         $count_tudo_ok ✅\n";
echo "Divergências:           $count_divergencias ⚠️\n";
echo "Não existem no BD:      $count_nao_existe ❌\n";
echo "Erros:                  $count_erros 🚨\n";
echo "---------------------------------------------\n";
echo "HTML gerado: $caminho_html\n";
echo "=============================================\n";
