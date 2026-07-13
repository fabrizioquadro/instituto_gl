<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ESTOQUES DE MOUNJARO 60MG (ID 45) NA CLINICA 5 ===\n";
$stocks = App\Models\Estoque::where('medicamento_id', 45)
    ->where('clinica_id', 5)
    ->orderBy('id', 'desc')
    ->get();
foreach ($stocks as $s) {
    echo "ID: " . $s->id . " | Lote: " . $s->lote . " | CB: " . $s->codigo_barras . " | Tipo: " . $s->tipo . " | Qtd: " . $s->quantidade . " | Criado: " . $s->created_at . "\n";
}

echo "\n=== ESTOQUES DE MOUNJARO 90MG (ID 9) NA CLINICA 5 ===\n";
$stocks9 = App\Models\Estoque::where('medicamento_id', 9)
    ->where('clinica_id', 5)
    ->orderBy('id', 'desc')
    ->get();
foreach ($stocks9 as $s) {
    echo "ID: " . $s->id . " | Lote: " . $s->lote . " | CB: " . $s->codigo_barras . " | Tipo: " . $s->tipo . " | Qtd: " . $s->quantidade . " | Criado: " . $s->created_at . "\n";
}
