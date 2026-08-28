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
use App\Models\FinanceiroProcedimento;

$proc = Procedimento::with('paciente', 'aplicacaos.medicamento')->where('id', 42032)->first();

if (!$proc) {
    echo "ERRO: Procedimento 42032 não encontrado.\n";
    exit(1);
}

echo "========== PROCEDIMENTO 42032 ==========\n";
echo "id: {$proc->id}\n";
echo "codigo: {$proc->codigo}\n";
echo "nr_procedimento: {$proc->nr_procedimento}\n";
echo "paciente: " . ($proc->paciente ? $proc->paciente->nm_paciente . " (id {$proc->paciente->id}, feegow {$proc->paciente->paciente_id_feegow})" : 'NULL') . "\n";
echo "situacao: {$proc->situacao}\n";
echo "data_cad: {$proc->data_cad}\n";
echo "data_aplicacao: {$proc->data_aplicacao}\n";
echo "medico: {$proc->medico}\n";
echo "tipo_atendimento: {$proc->tipo_atendimento}\n";
echo "st_pagamento: {$proc->st_pagamento} | vl_pago: {$proc->vl_pago} | valor: {$proc->valor}\n";
echo "obs: {$proc->obs}\n\n";

echo "========== REGISTROS RELACIONADOS ==========\n";

// Aplicações
$aplicacoes = Aplicacao::where('procedimento_id', 42032)->get();
echo "Aplicações (" . count($aplicacoes) . "):\n";
foreach ($aplicacoes as $a) {
    echo "  - id {$a->id} | medicamento: " . ($a->medicamento ? $a->medicamento->nome . " (unidade: {$a->medicamento->unidade})" : '?') . " | situacao: {$a->situacao} | qtde: {$a->quantidade}\n";

    // Lotes da aplicação
    $lotes = AplicacaoLote::where('aplicacao_id', $a->id)->get();
    foreach ($lotes as $l) {
        echo "      lote id {$l->id} | estoque_aberto_id: {$l->estoque_aberto_id} | quantidade: {$l->quantidade} | lote_nro: {$l->lote}\n";
    }
}
echo "\n";

// Anexos
$anexos = ProcedimentoAnexo::where('procedimento_id', 42032)->get();
echo "Anexos (" . count($anexos) . "):\n";
foreach ($anexos as $an) {
    echo "  - id {$an->id} | arquivo: {$an->arquivo} | enviado_feegow: {$an->enviado_feegow}\n";
}
echo "\n";

// Logs
$logs = ProcedimentoLog::where('procedimento_id', 42032)->get();
echo "Logs (" . count($logs) . "):\n";
foreach ($logs as $l) {
    echo "  - id {$l->id} | " . json_encode($l->toArray()) . "\n";
}
echo "\n";

// Observações
$obs = ProcedimentoObservacao::where('procedimento_id', 42032)->get();
echo "Observações (" . count($obs) . "):\n";
foreach ($obs as $o) {
    echo "  - id {$o->id} | " . json_encode($o->toArray()) . "\n";
}
echo "\n";

// Estoque (movimentos Saida origem Procedimento)
$estoques = Estoque::where('procedimento_id', 42032)->get();
echo "Movimentos de Estoque (" . count($estoques) . "):\n";
foreach ($estoques as $e) {
    echo "  - id {$e->id} | medicamento_id: {$e->medicamento_id} | origem: {$e->origem} | tipo: {$e->tipo} | quantidade: {$e->quantidade}\n";
}
echo "\n";

// EstoqueAberto referenciados via lotes de aplicações
echo "EstoqueAberto referenciados:\n";
$vistos = [];
foreach ($aplicacoes as $a) {
    $lotes = AplicacaoLote::where('aplicacao_id', $a->id)->get();
    foreach ($lotes as $l) {
        $aberto = EstoqueAberto::where('id', $l->estoque_aberto_id)->first();
        if ($aberto && !in_array($aberto->id, $vistos)) {
            $vistos[] = $aberto->id;
            echo "  - id {$aberto->id} | medicamento_id: {$aberto->medicamento_id} | qt_utilizado: {$aberto->qt_utilizado} | qt_restante: {$aberto->qt_restante} | situacao: {$aberto->situacao}\n";
        }
    }
}
if (empty($vistos)) {
    echo "  (nenhum)\n";
}
echo "\n";

// Financeiro
$fins = FinanceiroProcedimento::where('procedimento_id', 42032)->get();
echo "Vinculos Financeiro (" . count($fins) . "):\n";
foreach ($fins as $f) {
    echo "  - id {$f->id} | financeiro_id: {$f->financeiro_id}\n";
}
