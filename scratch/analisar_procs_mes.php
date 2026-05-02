<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;

$p_id = 21867;
$procs = Procedimento::where('paciente_id', $p_id)->where('data_cad', '>', '2026-04-01')->get();
echo "Procedimentos desde 01/04 para o paciente {$p_id}:\n";
foreach($procs as $p) {
    echo "ID: {$p->id} | DATE: {$p->data_cad} | VALOR: {$p->valor} | FIN: {$p->financeiro_id}\n";
}
