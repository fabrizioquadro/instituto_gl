<?php
/**
 * Inspeção do grupo de procedimentos 2293120260731140250
 * Mostra: procedimentos, situação, aplicações e logs (para saber o estado original)
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;
use App\Models\ProcedimentoLog;

$codigo = '2293120260731140250';

$procedimentos = Procedimento::where('codigo', $codigo)->orderBy('id')->get();

echo "=== PROCEDIMENTOS DO GRUPO $codigo ===\n";
foreach ($procedimentos as $p) {
    echo "ID {$p->id} | nr_proc {$p->nr_procedimento} | situacao '{$p->situacao}' | data_aplicacao ".
        ($p->data_aplicacao ?: 'null') . " | valor {$p->valor} | st_pagamento '{$p->st_pagamento}' | semana_sem_aplicacao '" . ($p->semana_sem_aplicacao ?: 'null') . "'\n";

    foreach ($p->aplicacaos as $a) {
        echo "   aplicacao id {$a->id} | medicamento_id {$a->medicamento_id} | situacao '{$a->situacao}' | qtd {$a->quantidade} | total {$a->total}\n";
    }
}

echo "\n=== LOGS (procedimento_logs) ===\n";
foreach ($procedimentos as $p) {
    $logs = ProcedimentoLog::where('procedimento_id', $p->id)
        ->where(function($q){
            $q->where('acao', 'like', '%Atualiza%')
              ->orWhere('acao', 'like', '%Cancel%')
              ->orWhere('acao', 'like', '%Aplica%')
              ->orWhere('acao', 'like', '%Cria%');
        })
        ->orderBy('id')->get();
    if ($logs->count() === 0) continue;
    echo "--- Procedimento ID {$p->id} ---\n";
    foreach ($logs as $l) {
        echo "[{$l->id}] {$l->created_at} | acao '{$l->acao}' | {$l->descricao}\n";
        if (!empty($l->dados_antigos) || !empty($l->dados_novos)) {
            echo "    antigos: " . json_encode($l->dados_antigos, JSON_UNESCAPED_UNICODE) . "\n";
            echo "    novos:   " . json_encode($l->dados_novos, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}
