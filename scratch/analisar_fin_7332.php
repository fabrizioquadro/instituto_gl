<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Financeiro;

$fin_id = 7332;
$fin = Financeiro::find($fin_id);

if($fin) {
    echo "Financeiro ID: {$fin->id}\n";
    echo "Paciente: {$fin->paciente->nm_paciente}\n";
    echo "VL_PROCEDIMENTOS: {$fin->vl_procedimentos}\n";
    echo "VL_CONSULTA: {$fin->vl_consulta}\n";
    echo "VL_DESCONTO: {$fin->vl_desconto}\n";
    echo "VL_ADICIONAL: {$fin->vl_adicional}\n";
    
    echo "\nPagamentos:\n";
    foreach($fin->formas as $f) {
        echo "- ID: {$f->id} | Forma: {$f->forma_pagamento} | Valor: {$f->vl_pagamento} | ID_PG: {$f->id_pagamento} | Data: {$f->created_at}\n";
    }
    
    $total_pago = $fin->formas->sum('vl_pagamento');
    echo "\nTOTAL PAGO: {$total_pago}\n";
    
    $total_devido = $fin->vl_procedimentos + $fin->vl_consulta + $fin->vl_adicional - $fin->vl_desconto;
    echo "TOTAL DEVIDO: {$total_devido}\n";
} else {
    echo "Financeiro ID {$fin_id} não encontrado.\n";
}
