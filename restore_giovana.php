<?php

use Illuminate\Support\Facades\DB;
use App\Models\Procedimento;
use App\Models\Aplicacao;
use App\Models\Medicamento;
use App\Models\ProcedimentoLog;

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$codigo = '2253920260617174235';
$deleted_ids = [37520, 37521, 37523, 37531];
$is_execute = in_array('--execute', $argv);

echo "=========================================================\n";
echo "SISTEMA DE RESTAURAÇÃO DE PROCEDIMENTOS - CASO GIOVANA\n";
echo "=========================================================\n";
echo "Código do Grupo: $codigo\n";
echo "Modo: " . ($is_execute ? "EXECUTAR (Gravar no Banco)" : "SIMULAÇÃO (Dry-run)") . "\n";
echo "=========================================================\n\n";

// 1. Get a template procedure from the existing ones in the same group
$template = Procedimento::where('codigo', $codigo)->first();
if (!$template) {
    echo "ERRO: Nenhum procedimento encontrado para o grupo $codigo. Não é possível usar como template.\n";
    exit(1);
}
echo "Procedimento de Template encontrado: ID {$template->id} | Paciente ID: {$template->paciente_id} | Médico: {$template->medico}\n\n";

// 2. Fetch all logs for the deleted procedures
$logs = DB::table('procedimento_logs')
    ->whereIn('procedimento_id', $deleted_ids)
    ->orderBy('created_at', 'asc')
    ->orderBy('id', 'asc')
    ->get();

echo "Encontrados " . count($logs) . " registros de log associados aos IDs deletados.\n\n";

// Group logs by procedure_id to reconstruct them
$logs_by_proc = [];
foreach ($logs as $log) {
    $logs_by_proc[$log->procedimento_id][] = $log;
}

foreach ($deleted_ids as $id) {
    echo "---------------------------------------------------------\n";
    echo "Analisando ID do Procedimento: $id\n";
    echo "---------------------------------------------------------\n";
    
    $proc_logs = $logs_by_proc[$id] ?? [];
    if (empty($proc_logs)) {
        echo "Nenhum log encontrado para o procedimento ID $id.\n";
        continue;
    }
    
    // We will reconstruct the applications list by replaying the logs
    $reconstructed_applications = [];
    $nr_procedimento = $id - 37520 + 1; // Sequential week number
    
    echo "Número da Semana Reconstruída: Semana $nr_procedimento\n";
    
    // Find the exclusion log to identify when the procedure was deleted
    $exclusao_log = null;
    foreach ($proc_logs as $log) {
        if ($log->acao == 'Exclusão') {
            $exclusao_log = $log;
            break;
        }
    }
    $exclusao_time = $exclusao_log ? $exclusao_log->created_at : null;
    
    foreach ($proc_logs as $log) {
        // If this log happened during the deletion cascade of the week itself, we ignore it.
        if ($exclusao_time && abs(strtotime($log->created_at) - strtotime($exclusao_time)) <= 2) {
            continue;
        }
        
        $dados_novos = json_decode($log->dados_novos, true);
        $dados_antigos = json_decode($log->dados_antigos, true);
        
        switch ($log->acao) {
            case 'Adição de Medicamento':
                if (isset($dados_novos['medicamento'])) {
                    $nome_med = $dados_novos['medicamento'];
                    $quantidade = $dados_novos['quantidade'] ?? 1;
                    $total = $dados_novos['total'] ?? 0;
                    
                    $reconstructed_applications[$nome_med] = [
                        'medicamento_nome' => $nome_med,
                        'quantidade' => $quantidade,
                        'total' => $total,
                        'situacao' => 'Aberta',
                        'obs' => '',
                    ];
                    echo "  [LOG] Adicionado medicamento: $nome_med (Qtd: $quantidade, Total: R$ $total)\n";
                }
                break;
                
            case 'Alteração de Medicamento':
                // Check dirty fields in dados_novos and update the record
                if (isset($dados_antigos['medicamento_id']) || isset($dados_antigos['id'])) {
                    // Try to find by old name or ID
                    $old_med_id = $dados_antigos['medicamento_id'] ?? null;
                    $old_med = $old_med_id ? Medicamento::find($old_med_id) : null;
                    $nome_key = $old_med ? $old_med->nome : null;
                    
                    if ($nome_key && isset($reconstructed_applications[$nome_key])) {
                        $app = $reconstructed_applications[$nome_key];
                        unset($reconstructed_applications[$nome_key]); // Remove old key in case name changed
                        
                        $new_med_id = $dados_novos['medicamento_id'] ?? $old_med_id;
                        $new_med = Medicamento::find($new_med_id);
                        $new_nome = $new_med ? $new_med->nome : $nome_key;
                        
                        $app['medicamento_nome'] = $new_nome;
                        if (isset($dados_novos['quantidade'])) $app['quantidade'] = $dados_novos['quantidade'];
                        if (isset($dados_novos['total'])) $app['total'] = $dados_novos['total'];
                        if (isset($dados_novos['obs'])) $app['obs'] = $dados_novos['obs'];
                        if (isset($dados_novos['situacao'])) $app['situacao'] = $dados_novos['situacao'];
                        
                        $reconstructed_applications[$new_nome] = $app;
                        echo "  [LOG] Editado medicamento: $new_nome (Qtd: {$app['quantidade']}, Total: R$ {$app['total']})\n";
                    }
                }
                break;
                
            case 'Remoção de Medicamento':
                // "O medicamento \"$nome_med\" foi removido do procedimento."
                preg_match('/O medicamento "([^"]+)" foi removido/', $log->descricao, $matches);
                if (isset($matches[1])) {
                    $nome_med = $matches[1];
                    if (isset($reconstructed_applications[$nome_med])) {
                        unset($reconstructed_applications[$nome_med]);
                        echo "  [LOG] Removido medicamento: $nome_med\n";
                    }
                }
                break;
                
            case 'Aplicação Realizada':
                // "O medicamento \"$nome_med\" foi aplicado com sucesso."
                preg_match('/O medicamento "([^"]+)" foi aplicado/', $log->descricao, $matches);
                if (isset($matches[1])) {
                    $nome_med = $matches[1];
                    if (isset($reconstructed_applications[$nome_med])) {
                        $reconstructed_applications[$nome_med]['situacao'] = 'Aplicada';
                        echo "  [LOG] Marcado como Aplicado: $nome_med\n";
                    }
                }
                break;
        }
    }
    
    // Determine target dates for the procedure
    $weeks_diff = $nr_procedimento - 1;
    $original_date_cad = date('Y-m-d', strtotime($template->data_cad . " + " . ($weeks_diff * 7) . " days"));
    
    // Reconstruct the procedure record
    $proc_data = [
        'id' => $id,
        'codigo' => $template->codigo,
        'nr_procedimento' => $nr_procedimento,
        'clinica_id' => $template->clinica_id,
        'clinica_id_aplicacao' => $template->clinica_id_aplicacao,
        'paciente_id' => $template->paciente_id,
        'user_id_aplicacao' => $template->user_id_aplicacao,
        'data_cad' => $original_date_cad,
        'situacao' => ($nr_procedimento == 1) ? 'Aplicado' : 'Agendado',
        'data_aplicacao' => ($nr_procedimento == 1) ? '2026-07-03' : $template->data_aplicacao,
        'dt_hr_chegada' => ($nr_procedimento == 1) ? '2026-07-03 14:45:50' : $template->dt_hr_chegada,
        'dt_hr_atendimento' => ($nr_procedimento == 1) ? '2026-07-03 14:45:50' : $template->dt_hr_atendimento,
        'dt_hr_finalizacao' => ($nr_procedimento == 1) ? '2026-07-03 14:45:50' : $template->dt_hr_finalizacao,
        'medico' => $template->medico,
        'st_pagamento' => $template->st_pagamento,
        'tipo_pagamento' => $template->tipo_pagamento,
        'forma_pagamento' => $template->forma_pagamento,
        'parcelas' => $template->parcelas,
        'vl_pago' => ($nr_procedimento == 1) ? $template->vl_pago : '0.00',
        'valor' => array_sum(array_column($reconstructed_applications, 'total')),
        'user_id_cadastro' => $template->user_id_cadastro,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    
    echo "\n  PROCEDIMENTO A SER RESTAURADO:\n";
    echo "  - ID: {$proc_data['id']}\n";
    echo "  - Semana: {$proc_data['nr_procedimento']}\n";
    echo "  - Situação: {$proc_data['situacao']}\n";
    echo "  - Data Agendamento: {$proc_data['data_cad']}\n";
    echo "  - Data Aplicação: " . ($proc_data['data_aplicacao'] ?? 'Nulo') . "\n";
    echo "  - Valor Calculado: R$ " . number_format($proc_data['valor'], 2, ',', '.') . "\n";
    echo "  - Medicamentos:\n";
    foreach ($reconstructed_applications as $app) {
        echo "    * {$app['medicamento_nome']} | Qtd: {$app['quantidade']} | Total: R$ {$app['total']} | Situação: {$app['situacao']}\n";
    }
    
    if ($is_execute) {
        // Run database queries
        DB::beginTransaction();
        try {
            // Remove existing references just in case (to avoid conflict)
            DB::table('procedimentos')->where('id', $id)->delete();
            
            // Insert procedure preserving the ID
            DB::table('procedimentos')->insert($proc_data);
            
            // Insert applications
            foreach ($reconstructed_applications as $app_data) {
                $med = Medicamento::where('nome', $app_data['medicamento_nome'])->first();
                if (!$med) {
                    throw new \Exception("Medicamento não encontrado no banco: " . $app_data['medicamento_nome']);
                }
                
                $max_app_id = DB::table('aplicacaos')->max('id') ?? 0;
                $new_app_id = $max_app_id + 1;
                
                $app_insert = [
                    'id' => $new_app_id,
                    'procedimento_id' => $id,
                    'medicamento_id' => $med->id,
                    'quantidade' => $app_data['quantidade'],
                    'valor' => $app_data['total'] / $app_data['quantidade'],
                    'total' => $app_data['total'],
                    'situacao' => ($nr_procedimento == 1) ? 'Aplicada' : $app_data['situacao'],
                    'obs' => $app_data['obs'],
                    'user_id_aplicacao' => ($nr_procedimento == 1) ? $template->user_id_aplicacao : null,
                    'dt_hr_chegada' => ($nr_procedimento == 1) ? '2026-07-03 14:45:50' : null,
                    'dt_hr_atendimento' => ($nr_procedimento == 1) ? '2026-07-03 14:45:50' : null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                
                DB::table('aplicacaos')->insert($app_insert);
                
                // If it is week 1 and it was applied, we also need to reconstruct the AplicacaoLote record!
                // We will try to find if there is a log description mentioning the lote for this medication,
                // or if we can find a matching unused lot in the clinic's stock.
                if ($nr_procedimento == 1) {
                    // Let's check if the log description contains any detail
                    // (Actually, lot is in AplicacaoLote, which does not have logs,
                    // but we can query existing lot entries for the clinic or associate a default/dummy/last used lot).
                    // We'll search for the last lot used for this medication in the clinic
                    $ultimo_lote_usado = DB::table('aplicacao_lotes')
                        ->join('aplicacaos', 'aplicacao_lotes.aplicacao_id', '=', 'aplicacaos.id')
                        ->where('aplicacaos.medicamento_id', $med->id)
                        ->orderBy('aplicacao_lotes.id', 'desc')
                        ->first();
                        
                    $lote_nome = $ultimo_lote_usado ? $ultimo_lote_usado->lote : 'RESTORED';
                    $codigo_barras = $ultimo_lote_usado ? $ultimo_lote_usado->codigo_barras : 'RESTORED';
                    
                    $max_lote_id = DB::table('aplicacao_lotes')->max('id') ?? 0;
                    
                    DB::table('aplicacao_lotes')->insert([
                        'id' => $max_lote_id + 1,
                        'aplicacao_id' => $new_app_id,
                        'quantidade' => $app_data['quantidade'],
                        'lote' => $lote_nome,
                        'codigo_barras' => $codigo_barras,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    
                    $max_estoque_id = DB::table('estoques')->max('id') ?? 0;
                    
                    // Create corresponding Estoque record to balance stock
                    DB::table('estoques')->insert([
                        'id' => $max_estoque_id + 1,
                        'clinica_id' => $template->clinica_id_aplicacao ?? $template->clinica_id,
                        'procedimento_id' => $id,
                        'medicamento_id' => $med->id,
                        'origem' => 'Procedimento',
                        'tipo' => 'Saida',
                        'quantidade' => $app_data['quantidade'],
                        'valor' => 0,
                        'total' => 0,
                        'lote' => $lote_nome,
                        'codigo_barras' => $codigo_barras,
                        'dt_vencimento' => date('Y-m-d', strtotime('+6 months')),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            
            // Reassociate with finance if exists
            $financeiro_proc = DB::table('financeiro_procedimentos')
                ->where('procedimento_id', $template->id)
                ->first();
                
            if ($financeiro_proc) {
                $link_exists = DB::table('financeiro_procedimentos')
                    ->where('financeiro_id', $financeiro_proc->financeiro_id)
                    ->where('procedimento_id', $id)
                    ->exists();
                if (!$link_exists) {
                    $max_fin_proc_id = DB::table('financeiro_procedimentos')->max('id') ?? 0;
                    DB::table('financeiro_procedimentos')->insert([
                        'id' => $max_fin_proc_id + 1,
                        'financeiro_id' => $financeiro_proc->financeiro_id,
                        'procedimento_id' => $id,
                    ]);
                }
            }
            
            DB::commit();
            echo "  ✔ Sucesso: Procedimento $id restaurado com as suas aplicações no banco de dados!\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "  ✘ ERRO ao restaurar procedimento $id: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

if ($is_execute) {
    echo "=========================================================\n";
    echo "RESTAURAÇÃO CONCLUÍDA!\n";
    echo "=========================================================\n";
} else {
    echo "=========================================================\n";
    echo "DICA: Para executar a restauração real no banco de dados,\n";
    echo "rode o comando: php restore_giovana.php --execute\n";
    echo "=========================================================\n";
}
