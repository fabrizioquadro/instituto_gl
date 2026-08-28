<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;
use App\Models\Aplicacao;
use App\Models\AplicacaoLote;
use App\Models\ProcedimentoAnexo;
use App\Models\ProcedimentoLog;
use App\Models\ProcedimentoObservacao;
use App\Models\Estoque;
use App\Models\EstoqueAberto;
use App\Models\Financeiro;
use App\Models\FinanceiroProcedimento;
use App\Models\FinanceiroFormasPagamento;
use Illuminate\Support\Facades\DB;

$procedimento_id = 44220;

$resultado = DB::transaction(function () use ($procedimento_id) {
    $deletados = [];

    $procedimento = Procedimento::with('aplicacaos.medicamento')->find($procedimento_id);
    if (!$procedimento) {
        throw new \Exception("Procedimento $procedimento_id não encontrado.");
    }

    $deletados['procedimento'] = "{$procedimento->id} (codigo {$procedimento->codigo}, paciente {$procedimento->paciente_id})";

    // 1. Anexos
    $anexos = ProcedimentoAnexo::where('procedimento_id', $procedimento->id)->count();
    ProcedimentoAnexo::where('procedimento_id', $procedimento->id)->delete();
    $deletados['anexos'] = $anexos;

    // 2. Vínculos financeiros (pivot)
    $pivots = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->get();
    $financeiro_ids = $pivots->pluck('financeiro_id')->unique()->values()->toArray();
    FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->delete();
    $deletados['vinculos_financeiro'] = $pivots->count();

    // 3. Aplicações (mesma lógica do delete_procedimento do sistema)
    $qt_aplicacoes = 0;
    $qt_lotes = 0;
    foreach ($procedimento->aplicacaos as $aplicacao) {
        $qt_aplicacoes++;
        if ($aplicacao->situacao == "Aplicada") {
            if ($aplicacao->medicamento && $aplicacao->medicamento->unidade == "Ampola") {
                AplicacaoLote::where('aplicacao_id', $aplicacao->id)->delete();
                Estoque::where('origem', 'Procedimento')
                    ->where('tipo', 'Saida')
                    ->where('procedimento_id', $procedimento->id)
                    ->where('medicamento_id', $aplicacao->medicamento->id)
                    ->delete();
            } elseif ($aplicacao->medicamento && $aplicacao->medicamento->unidade == "Miligrama") {
                $aplic_lotes = AplicacaoLote::where('aplicacao_id', $aplicacao->id)->get();
                foreach ($aplic_lotes as $lote) {
                    $qt_lotes++;
                    $aberto = EstoqueAberto::find($lote->estoque_aberto_id);
                    if ($aberto) {
                        $aberto->qt_utilizado -= $lote->quantidade;
                        $aberto->qt_restante += $lote->quantidade;
                        if ($aberto->qt_restante > 0) {
                            $aberto->situacao = 'Aberto';
                        }
                        $aberto->save();
                    }
                    $lote->delete();
                }
            }
        }
        $aplicacao->delete();
    }
    $deletados['aplicacoes'] = $qt_aplicacoes;
    $deletados['aplicacao_lotes'] = $qt_lotes;

    // 4. Logs (o sistema não remove, mas aqui faremos a limpeza completa)
    $logs = ProcedimentoLog::where('procedimento_id', $procedimento->id)->count();
    ProcedimentoLog::where('procedimento_id', $procedimento->id)->delete();
    $deletados['logs'] = $logs;

    // 5. Observações
    $obs = ProcedimentoObservacao::where('procedimento_id', $procedimento->id)->count();
    ProcedimentoObservacao::where('procedimento_id', $procedimento->id)->delete();
    $deletados['observacoes'] = $obs;

    // 6. Financeiro vinculado (deletar por completo conforme decisão do usuário)
    $qt_financeiros = 0;
    $qt_formas = 0;
    foreach ($financeiro_ids as $fid) {
        // verifica se sobrou algum outro procedimento vinculado a este financeiro
        $aindaVinculado = FinanceiroProcedimento::where('financeiro_id', $fid)->exists();
        if (!$aindaVinculado) {
            $fin = Financeiro::find($fid);
            if ($fin) {
                $qt_financeiros++;
                $qt_formas += FinanceiroFormasPagamento::where('financeiro_id', $fid)->count();
                FinanceiroFormasPagamento::where('financeiro_id', $fid)->delete();
                $fin->delete();
            }
        }
    }
    $deletados['financeiros'] = $qt_financeiros;
    $deletados['formas_pagamento'] = $qt_formas;

    // 7. Procedimento
    $procedimento->delete();

    return $deletados;
});

echo "✅ EXCLUSÃO CONCLUÍDA COM SUCESSO (transação commitada)\n";
echo "------------------------------------------------------\n";
foreach ($resultado as $item => $qt) {
    echo str_pad($item, 22) . ": $qt\n";
}

// Confirmação pós-exclusão
$existe = Procedimento::find($procedimento_id);
$fin = Financeiro::find(11470);
echo "\nConfirmação:\n";
echo "  Procedimento 44220 existe? " . ($existe ? 'SIM (ERRO!)' : 'NÃO (removido)') . "\n";
echo "  Financeiro 11470 existe? " . ($fin ? 'SIM (ERRO!)' : 'NÃO (removido)') . "\n";
