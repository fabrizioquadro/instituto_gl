<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ESTOQUES ABERTOS ===\n";
$abertos = App\Models\EstoqueAberto::whereIn('medicamento_id', [9, 45])->get();
foreach ($abertos as $a) {
    echo "ID: " . $a->id . " | Med ID: " . $a->medicamento_id . " | Clinica: " . $a->clinica_id . " | Lote: " . $a->lote . " | Qt Inicial: " . $a->qt_inical . " | Qt Utilizado: " . $a->qt_utilizado . " | Qt Restante: " . $a->qt_restante . " | Situação: " . $a->situacao . "\n";
}

echo "\n=== ESTOQUE (SALDO GERAL) ===\n";
foreach ([9, 45] as $id) {
    $med = App\Models\Medicamento::find($id);
    echo "Med: " . $med->nome . " (ID: $id)\n";
    $stocks = App\Models\Estoque::where('medicamento_id', $id)->get();
    // Sum by type for each clinica
    $summary = [];
    foreach ($stocks as $s) {
        $key = $s->clinica_id . "_" . $s->lote;
        if (!isset($summary[$key])) {
            $summary[$key] = ['entrada' => 0, 'saida' => 0, 'clinica' => $s->clinica_id, 'lote' => $s->lote];
        }
        if ($s->tipo == 'Entrada') {
            $summary[$key]['entrada'] += $s->quantidade;
        } else {
            $summary[$key]['saida'] += $s->quantidade;
        }
    }
    foreach ($summary as $k => $sum) {
        $saldo = $sum['entrada'] - $sum['saida'];
        echo "  Clinica: " . $sum['clinica'] . " | Lote: " . $sum['lote'] . " | Entrada: " . $sum['entrada'] . " | Saida: " . $sum['saida'] . " | Saldo: " . $saldo . "\n";
    }
}
