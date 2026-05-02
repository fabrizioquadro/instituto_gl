<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;
use App\Models\Financeiro;

$codigo = '2186720260429104918';
$procs = Procedimento::where('codigo', $codigo)->get();
echo "Procedimentos encontrados: " . $procs->count() . "\n";

foreach($procs as $p) {
    echo "ID: {$p->id} | PAC: {$p->paciente_id} | FIN: {$p->financeiro_id} | VALOR: {$p->valor}\n";
}

if($procs->count() > 0) {
    $p_id = $procs->first()->paciente_id;
    echo "\nBuscando financeiro recente para o paciente ID: {$p_id}\n";
    $fins = Financeiro::where('paciente_id', $p_id)->orderBy('id', 'desc')->take(3)->get();
    foreach($fins as $f) {
        echo "FIN ID: {$f->id} | VL_PROC: {$f->vl_procedimentos} | VL_TOTAL_PAGO: " . $f->formas->sum('vl_pagamento') . " | DATA: {$f->created_at}\n";
    }
}
