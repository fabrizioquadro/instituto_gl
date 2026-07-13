<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$procedimentos = \App\Models\Procedimento::where('codigo', '2253920260617174235')->orderBy('id')->get();
foreach ($procedimentos as $p) {
    echo "ID: {$p->id} | Nr: {$p->nr_procedimento} | Data: {$p->data_aplicacao} | Situação: {$p->situacao}\n";
    foreach ($p->aplicacaos as $a) {
        $medName = $a->medicamento ? $a->medicamento->nome : 'N/A';
        echo "   - Aplicacao ID: {$a->id} | Med: {$medName} | Situação: {$a->situacao} | Qtd: {$a->quantidade} | Total: {$a->total}\n";
    }
}
