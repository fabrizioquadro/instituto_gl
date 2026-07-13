<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== APPLICACAO_LOTES MULTIPLE FOR MOUNJARO 90MG ===\n";
$aps9 = App\Models\Aplicacao::where('medicamento_id', 9)
    ->where('situacao', 'Aplicada')
    ->get();
foreach ($aps9 as $ap) {
    $lotes = App\Models\AplicacaoLote::where('aplicacao_id', $ap->id)->get();
    if ($lotes->count() > 1) {
        echo "ID: " . $ap->id . " | Prescribed: " . $ap->quantidade . " | Lotes: " . $lotes->count() . "\n";
        foreach ($lotes as $l) {
            echo "  - Lote: " . $l->lote . " | Qty: " . $l->quantidade . " | CB: " . $l->codigo_barras . " | Open ID: " . $l->estoque_aberto_id . "\n";
        }
    }
}

echo "\n=== APPLICACAO_LOTES MULTIPLE FOR MOUNJARO 60MG ===\n";
$aps45 = App\Models\Aplicacao::where('medicamento_id', 45)
    ->where('situacao', 'Aplicada')
    ->get();
foreach ($aps45 as $ap) {
    $lotes = App\Models\AplicacaoLote::where('aplicacao_id', $ap->id)->get();
    if ($lotes->count() > 1) {
        echo "ID: " . $ap->id . " | Prescribed: " . $ap->quantidade . " | Lotes: " . $lotes->count() . "\n";
        foreach ($lotes as $l) {
            echo "  - Lote: " . $l->lote . " | Qty: " . $l->quantidade . " | CB: " . $l->codigo_barras . " | Open ID: " . $l->estoque_aberto_id . "\n";
        }
    }
}
