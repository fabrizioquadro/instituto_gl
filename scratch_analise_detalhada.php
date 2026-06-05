<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;
use App\Models\ProcedimentoLog;

$ontem = date('Y-m-d', strtotime('-1 day'));

// Pegar os 23 não identificados
$procedimentos_nao_identificados = Procedimento::where('situacao','Aplicado')
    ->where('data_aplicacao', $ontem)
    ->whereNull('user_id_aplicacao')
    ->get();

// Pegar uma amostra de identificados
$procedimentos_identificados = Procedimento::where('situacao','Aplicado')
    ->where('data_aplicacao', $ontem)
    ->whereNotNull('user_id_aplicacao')
    ->limit(10)
    ->get();

echo "Analisando 23 Não Identificados...\n";
foreach($procedimentos_nao_identificados as $p) {
    // Verificar aplicacoes
    $aplicacoes = $p->aplicacaos;
    $users_aplicacao = [];
    foreach($aplicacoes as $ap) {
        $users_aplicacao[] = $ap->user_id_aplicacao;
    }
    
    // Verificar logs
    $logs = ProcedimentoLog::where('procedimento_id', $p->id)->get();
    $log_actions = [];
    foreach($logs as $log) {
        $log_actions[] = $log->acao . ' (por User ' . $log->user_id . ')';
    }

    echo "Proc ID: {$p->id} | Cadastrado por: {$p->user_id_cadastro} | Tipo: {$p->tipo_atendimento} | ";
    echo "Users na Aplicacao (Itens): " . implode(', ', array_unique($users_aplicacao)) . " | ";
    echo "Logs: " . implode(' -> ', $log_actions) . "\n";
}

echo "\nAnalisando Amostra Identificados...\n";
foreach($procedimentos_identificados as $p) {
    $aplicacoes = $p->aplicacaos;
    $users_aplicacao = [];
    foreach($aplicacoes as $ap) {
        $users_aplicacao[] = $ap->user_id_aplicacao;
    }
    
    $logs = ProcedimentoLog::where('procedimento_id', $p->id)->get();
    $log_actions = [];
    foreach($logs as $log) {
        $log_actions[] = $log->acao . ' (por User ' . $log->user_id . ')';
    }

    echo "Proc ID: {$p->id} | Cadastrado por: {$p->user_id_cadastro} | Tipo: {$p->tipo_atendimento} | ";
    echo "Users na Aplicacao (Itens): " . implode(', ', array_unique($users_aplicacao)) . " | ";
    echo "Logs: " . implode(' -> ', $log_actions) . "\n";
}
