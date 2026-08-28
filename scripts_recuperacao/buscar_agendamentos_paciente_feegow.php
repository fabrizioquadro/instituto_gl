<?php
/**
 * Script para buscar os agendamentos de um determinado paciente na Feegow
 * e gerar um relatório HTML.
 *
 * Uso: php scripts_recuperacao/buscar_agendamentos_paciente_feegow.php <paciente_id> [data_inicio] [data_fim]
 *
 * Exemplos:
 *   php scripts_recuperacao/buscar_agendamentos_paciente_feegow.php 16988
 *   php scripts_recuperacao/buscar_agendamentos_paciente_feegow.php 16988 01-06-2026 10-08-2026
 *   php scripts_recuperacao/buscar_agendamentos_paciente_feegow.php 16988 2026-06-01 2026-08-10
 *
 * Datas aceitas nos formatos dd-mm-aaaa ou aaaa-mm-dd.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Paciente;

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmZWVnb3ciLCJhdWQiOiJwdWJsaWNhcGkiLCJpYXQiOjE3NTE4OTczODYsImxpY2Vuc2VJRCI6MjMyMjR9.ZC8gSWEiCJsLa7AoFUOT074zaRNECddfXJNT_zi8RvI";

// ==============================================
// ARGUMENTOS
// ==============================================
$paciente_id = $argv[1] ?? null;
if (!$paciente_id || !is_numeric($paciente_id)) {
    die("Uso: php buscar_agendamentos_paciente_feegow.php <paciente_id> [data_inicio] [data_fim]\n");
}

function normalizar_data_feegow($valor) {
    $valor = str_replace('/', '-', trim($valor));
    $partes = explode('-', $valor);
    if (count($partes) !== 3) return null;
    if (strlen($partes[0]) === 4) {
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0]; // aaaa-mm-dd -> dd-mm-aaaa
    }
    return $valor; // já é dd-mm-aaaa
}

$data_inicio = isset($argv[2]) ? normalizar_data_feegow($argv[2]) : date('d-m-Y', strtotime('-180 days'));
$data_fim = isset($argv[3]) ? normalizar_data_feegow($argv[3]) : date('d-m-Y');

if (!$data_inicio || !$data_fim) {
    die("Formato de data inválido. Use dd-mm-aaaa ou aaaa-mm-dd.\n");
}

echo "=============================================\n";
echo " BUSCAR AGENDAMENTOS DO PACIENTE (FEEGOW)\n";
echo "=============================================\n";
echo "Paciente ID: $paciente_id\n";
echo "Período: $data_inicio até $data_fim\n\n";

// ==============================================
// FUNÇÃO AUXILIAR DE CHAMADA À API
// ==============================================
function chamar_api_feegow($url, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-access-token: $token",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $erro = curl_error($ch);
    curl_close($ch);
    if ($erro) {
        return ['erro' => $erro];
    }
    return json_decode($response, true);
}

// ==============================================
// NOME DO PACIENTE (local ou Feegow)
// ==============================================
$nome_paciente = 'N/A';
$paciente_local = Paciente::where('paciente_id_feegow', $paciente_id)->first();
if ($paciente_local) {
    $nome_paciente = $paciente_local->nm_paciente;
    echo "Paciente (local): $nome_paciente\n";
} else {
    $ret = chamar_api_feegow("https://api.feegow.com/v1/api/patient/search?paciente_id=" . $paciente_id . "&photo=false", $token);
    if (isset($ret['success']) && $ret['success'] && isset($ret['content']['nome'])) {
        $nome_paciente = $ret['content']['nome'] ?: ($ret['content']['nome_social'] ?? 'N/A');
        echo "Paciente (Feegow): $nome_paciente\n";
    }
}

// ==============================================
// STATUS DISPONÍVEIS NA FEEGOW
// ==============================================
$mapa_status = [];
$ret_status = chamar_api_feegow("https://api.feegow.com/v1/api/appoints/status", $token);
if (isset($ret_status['success']) && $ret_status['success']) {
    foreach ($ret_status['content'] as $s) {
        $mapa_status[$s['id']] = $s['status'];
    }
}

// ==============================================
// BUSCAR AGENDAMENTOS (com paginação)
// ==============================================
echo "Buscando agendamentos...\n";

$todos = [];
$limit = 1000;
$offset = 0;

while (true) {
    $parametros = [
        'data_start' => $data_inicio,
        'data_end' => $data_fim,
        'paciente_id' => $paciente_id,
        'limit' => $limit,
        'offset' => $offset,
    ];
    $ret = chamar_api_feegow("https://api.feegow.com/v1/api/appoints/search?" . http_build_query($parametros), $token);

    if (isset($ret['erro'])) {
        die("Erro de conexão: {$ret['erro']}\n");
    }
    if (!isset($ret['success']) || $ret['success'] !== true) {
        die("Erro da API: " . ($ret['message'] ?? 'desconhecido') . "\n");
    }

    $conteudo = $ret['content'] ?? [];
    if (empty($conteudo)) {
        break;
    }

    $agendamentos = $conteudo['appoints'] ?? $conteudo['agendamentos'] ?? $conteudo;
    if (!is_array($agendamentos)) {
        $agendamentos = [];
    }
    $todos = array_merge($todos, $agendamentos);

    $qtd = count($agendamentos);
    if ($qtd < $limit) {
        break;
    }
    $offset += $limit;
}

$total = count($todos);
echo "Total de agendamentos: $total\n\n";

// Converte dd-mm-aaaa (ou aaaa-mm-dd) para uma chave aaaa-mm-dd comparável
function chave_data_sortavel($data) {
    $data = trim($data);
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $data, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $m)) {
        return $data;
    }
    return '0000-00-00';
}

// Ordena por data e horário em ordem DECRESCENTE (mais recente primeiro)
usort($todos, function ($a, $b) {
    $cmp = strcmp(chave_data_sortavel($b['data'] ?? ''), chave_data_sortavel($a['data'] ?? ''));
    if ($cmp !== 0) return $cmp;
    return strcmp($b['horario'] ?? '00:00:00', $a['horario'] ?? '00:00:00');
});

// ==============================================
// GERAR HTML
// ==============================================
echo "Gerando relatório HTML...\n";

$GLOBALS['__data_geracao'] = date('d/m/Y H:i:s');

// Converte data dd-mm-aaaa para aaaa-mm-dd para o nome do arquivo
$inicio_arquivo = implode('-', array_reverse(explode('-', $data_inicio)));
$fim_arquivo = implode('-', array_reverse(explode('-', $data_fim)));
$nome_arquivo = 'agendamentos_paciente_' . $paciente_id . '_' . $inicio_arquivo . '_' . $fim_arquivo . '.html';
$caminho = __DIR__ . '/' . $nome_arquivo;

$status_cores = [
    'Atendido' => '#28a745',
    'Em atendimento' => '#007bff',
    'Chamando' => '#fd7e14',
    'Marcado - confirmado' => '#17a2b8',
    'Marcado - não confirmado' => '#ffc107',
    'Aguardando' => '#6c757d',
    'Aguardando pagamento' => '#6c757d',
    'Não compareceu' => '#dc3545',
    'Desmarcado pelo paciente' => '#dc3545',
    'Cancelado pelo profissional' => '#dc3545',
    'Remarcado' => '#6f42c1',
];

$linhas_html = '';
$cont_status = [];
foreach ($todos as $ag) {
    $status_id = $ag['status_id'] ?? '';
    $status_nome = $mapa_status[$status_id] ?? ('Status ' . $status_id);
    $cont_status[$status_nome] = ($cont_status[$status_nome] ?? 0) + 1;

    $cor = $status_cores[$status_nome] ?? '#333';

    $data_db = $ag['data'] ?? '';
    // dd-mm-aaaa -> aaaa-mm-dd para exibir
    $data_exib = $data_db;
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $data_db)) {
        $data_exib = implode('-', array_reverse(explode('-', $data_db)));
    }

    $horario = $ag['horario'] ?? '';
    if (preg_match('/^(\d{2}:\d{2})/', $horario, $m)) {
        $horario = $m[1];
    }

    $v_agendamento_id = $ag['agendamento_id'] ?? '';
    $v_paciente_id = $ag['paciente_id'] ?? '';
    $v_procedimento_id = $ag['procedimento_id'] ?? '';
    $v_valor = $ag['valor'] ?? '';
    $v_unidade = $ag['nome_fantasia'] ?? '';
    $v_agendado_por = $ag['agendado_por'] ?? '';
    $v_notas = $ag['notas'] ?? '';
    $v_agendado_em = $ag['agendado_em'] ?? '';

    $linhas_html .= "        <tr>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($v_agendamento_id) . "</td>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($data_exib) . "</td>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($horario) . "</td>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($v_paciente_id) . "</td>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($v_procedimento_id) . "</td>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($v_valor) . "</td>
            <td style=\"white-space:nowrap\"><span style=\"color:{$cor};font-weight:bold\">" . htmlspecialchars($status_nome) . "</span> <small style=\"color:#999\">(id {$status_id})</small></td>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($v_unidade) . "</td>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($v_agendado_por) . "</td>
            <td>" . htmlspecialchars($v_notas) . "</td>
            <td style=\"white-space:nowrap\">" . htmlspecialchars($v_agendado_em) . "</td>
        </tr>\n";
}

// Cards de resumo por status
$cards_html = '';
$i = 0;
foreach ($cont_status as $nome => $qtd) {
    $cor = $status_cores[$nome] ?? '#333';
    $cards_html .= "            <div class=\"card\" style=\"border-top:4px solid {$cor}\">
                <div class=\"numero\">{$qtd}</div>
                <div class=\"rotulo\">" . htmlspecialchars($nome) . "</div>
            </div>\n";
    $i++;
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos do Paciente - Feegow</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 10px; }
        .info { color: #666; margin-bottom: 20px; font-size: 14px; }
        .resumo { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .card {
            background: white; border-radius: 8px; padding: 15px 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; flex: 1; min-width: 150px;
        }
        .card .numero { font-size: 28px; font-weight: bold; }
        .card .rotulo { font-size: 12px; color: #666; text-transform: uppercase; margin-top: 5px; }
        .card.total .numero { color: #333; }
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
        .footer { margin-top: 20px; text-align: center; color: #999; font-size: 12px; }
        .sem-resultado { background: white; border-radius: 8px; padding: 40px; text-align: center; color: #666; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <h1>📅 Agendamentos do Paciente na Feegow</h1>
        <div class="info">
            Paciente: <strong>{$nome_paciente}</strong> (ID Feegow: {$paciente_id})<br>
            Período: {$data_inicio} até {$data_fim} | Gerado em: {$GLOBALS['__data_geracao']}
        </div>

        <div class="resumo">
            <div class="card total">
                <div class="numero">{$total}</div>
                <div class="rotulo">Total de Agendamentos</div>
            </div>
{$cards_html}        </div>

HTML;

if ($total === 0) {
    $html .= "        <div class=\"sem-resultado\">Nenhum agendamento encontrado para este paciente no período.</div>\n";
} else {
    $html .= <<<HTML
        <table>
            <thead>
                <tr>
                    <th>ID Agendamento</th>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>ID Paciente</th>
                    <th>ID Procedimento</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Unidade</th>
                    <th>Agendado Por</th>
                    <th>Notas</th>
                    <th>Agendado Em</th>
                </tr>
            </thead>
            <tbody>
{$linhas_html}            </tbody>
        </table>
HTML;
}

$html .= <<<HTML

        <div class="footer">Relatório gerado por scripts_recuperacao/buscar_agendamentos_paciente_feegow.php</div>
    </div>
</body>
</html>
HTML;

file_put_contents($caminho, $html);

echo "\n=============================================\n";
echo " ARQUIVO GERADO COM SUCESSO!\n";
echo "=============================================\n";
echo "Local: $caminho\n";
echo "Total de agendamentos: $total\n";
echo "=============================================\n";
