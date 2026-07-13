<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$aplicacaos = App\Models\Aplicacao::whereIn('medicamento_id', [9, 45])
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

foreach ($aplicacaos as $ap) {
    $proc = App\Models\Procedimento::find($ap->procedimento_id);
    $med = App\Models\Medicamento::find($ap->medicamento_id);
    echo "Aplicacao ID: " . $ap->id . " | Proc ID: " . $ap->procedimento_id . " | Proc Cod: " . ($proc ? $proc->codigo : 'N/A') . " | Med: " . $med->nome . " | Qty: " . $ap->quantidade . " | Situacao: " . $ap->situacao . " | Criado: " . $ap->created_at . "\n";
    
    // Check if there are lotes
    $lotes = App\Models\AplicacaoLote::where('aplicacao_id', $ap->id)->get();
    foreach ($lotes as $l) {
        echo "  - Lote: " . $l->lote . " | Qty: " . $l->quantidade . " | Codigo: " . $l->codigo_barras . " | Estoque Aberto ID: " . $l->estoque_aberto_id . "\n";
    }
}
