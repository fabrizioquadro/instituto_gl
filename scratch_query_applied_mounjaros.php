<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== APPLIED MOUNJARO APPLICATIONS ===\n";
$aplicacaos = App\Models\Aplicacao::whereIn('medicamento_id', [9, 45])
    ->where('situacao', 'Aplicada')
    ->orderBy('id', 'desc')
    ->get();

foreach ($aplicacaos as $ap) {
    $med = App\Models\Medicamento::find($ap->medicamento_id);
    $lotes = App\Models\AplicacaoLote::where('aplicacao_id', $ap->id)->get();
    echo "ID: " . $ap->id . " | Med: " . $med->nome . " | Prescribed Qty: " . $ap->quantidade . " | Lotes Count: " . $lotes->count() . "\n";
    foreach ($lotes as $l) {
        echo "  - Lote: " . $l->lote . " | Qty: " . $l->quantidade . " | CB: " . $l->codigo_barras . " | Open ID: " . $l->estoque_aberto_id . "\n";
    }
}
