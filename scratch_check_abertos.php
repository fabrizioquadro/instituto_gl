<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ESTOQUES ABERTOS (SITUAÇÃO: ABERTO) ===\n";
$abertos = App\Models\EstoqueAberto::whereIn('medicamento_id', [9, 45])
    ->where('situacao', 'Aberto')
    ->get();
foreach ($abertos as $a) {
    $med = App\Models\Medicamento::find($a->medicamento_id);
    $clinica = App\Models\Clinica::find($a->clinica_id);
    echo "ID: " . $a->id . " | Med: " . $med->nome . " (ID: " . $a->medicamento_id . ") | Clinica: " . ($clinica ? $clinica->nome : $a->clinica_id) . " | Lote: " . $a->lote . " | Qt Inicial: " . $a->qt_inical . " | Qt Utilizado: " . $a->qt_utilizado . " | Qt Restante: " . $a->qt_restante . "\n";
}
