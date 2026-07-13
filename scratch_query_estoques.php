<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$med = App\Models\Medicamento::where('nome', 'LIKE', '%MOUNJARO 60MG%')->first();
if ($med) {
    echo "MEDICAMENTO ID: " . $med->id . " | NOME: " . $med->nome . "\n\n";
    $lots = ['3224', '3227', '3231', '3253', '000159', '000189'];
    foreach ($lots as $lot) {
        echo "=== LOTE: $lot ===\n";
        $records = App\Models\Estoque::where('medicamento_id', $med->id)
            ->where('lote', $lot)
            ->get();
        foreach ($records as $r) {
            $clinica = App\Models\Clinica::find($r->clinica_id);
            echo "ID: " . $r->id . " | Clinica: " . ($clinica ? $clinica->nome : $r->clinica_id) . " | Tipo: " . $r->tipo . " | Qty: " . $r->quantidade . " | Venc: " . $r->dt_vencimento . " | Criado: " . $r->created_at . "\n";
        }
        echo "\n";
    }
} else {
    echo "MEDICAMENTO NOT FOUND\n";
}
