<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== APLICACAO_LOTES FOR MOUNJARO (9, 45) ===\n";
$lotes = App\Models\AplicacaoLote::whereIn('aplicacao_id', function($q) {
    $q->select('id')->from('aplicacaos')->whereIn('medicamento_id', [9, 45]);
})->orderBy('id', 'desc')->limit(30)->get();

foreach ($lotes as $l) {
    $ap = App\Models\Aplicacao::find($l->aplicacao_id);
    $med = App\Models\Medicamento::find($ap->medicamento_id);
    echo "Lote ID: " . $l->id . " | Aplicacao ID: " . $l->aplicacao_id . " | Med: " . $med->nome . " (ID: " . $med->id . ") | Qty: " . $l->quantidade . " | Lote: " . $l->lote . " | CB: " . $l->codigo_barras . " | Estoque Aberto ID: " . $l->estoque_aberto_id . "\n";
}
