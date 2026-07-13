<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$procs = App\Models\Procedimento::where('codigo', '2202620260630195653')->get();
foreach ($procs as $p) {
    echo "PROCEDIMENTO ID: " . $p->id . " | Codigo: " . $p->codigo . " | Semana: " . $p->nr_procedimento . " | Clinica: " . $p->clinica_id . " | Situação: " . $p->situacao . " | Data Aplicacao: " . $p->data_aplicacao . "\n";
    foreach ($p->aplicacaos as $ap) {
        $med = App\Models\Medicamento::find($ap->medicamento_id);
        echo "  - Aplicacao ID: " . $ap->id . " | Med: " . $med->nome . " | Qty: " . $ap->quantidade . " | Situacao: " . $ap->situacao . "\n";
    }
}
