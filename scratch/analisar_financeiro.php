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
    echo "ID: {$p->id} | NR: {$p->nr_procedimento} | VALOR: {$p->valor} | PG: {$p->st_pagamento} | SITUACAO: {$p->situacao}\n";
    foreach($p->aplicacaos as $a) {
        echo "  - Aplicacao: {$a->medicamento->nome} | Qtd: {$a->quantidade} | Valor: {$a->valor} | Total: {$a->total}\n";
    }
}

if($procs->count() > 0) {
    $proc = $procs->first();
    $finance_id = 5471; 
    echo "\nPrimeiro Procedimento ID: {$proc->id} | Financeiro ID usado: {$finance_id}\n";
    $fin = Financeiro::find($finance_id);
    if($fin) {
        echo "\nFinanceiro ID: {$fin->id}\n";
        echo "Paciente: {$fin->paciente->nm_paciente} (ID: {$fin->paciente_id})\n";
        echo "VL_CONSULTA: {$fin->vl_consulta}\n";
        echo "VL_PROCEDIMENTOS (cadastrado no financeiro): {$fin->vl_procedimentos}\n";
        echo "VL_DESCONTO: {$fin->vl_desconto}\n";
        echo "VL_ADICIONAL: {$fin->vl_adicional}\n";
        
        $pagos = $fin->formas()->sum('vl_pagamento');
        echo "TOTAL PAGO: {$pagos}\n";
        
        $soma_procs = $procs->sum('valor');
        echo "SOMA DOS VALORES DOS PROCEDIMENTOS NA LISTA: {$soma_procs}\n";

        $total_devido = $fin->vl_consulta + $soma_procs + $fin->vl_adicional - $fin->vl_desconto;
        echo "TOTAL DEVIDO CALCULADO (Consulta + Soma Procs + Adic - Desc): {$total_devido}\n";
        
        echo "\nFormas de Pagamento:\n";
        foreach($fin->formas as $f) {
            echo "- ID: {$f->id} | {$f->forma_pagamento} | Valor: {$f->vl_pagamento} | ID_PG: {$f->id_pagamento} | Data: {$f->created_at}\n";
        }

        if(abs($pagos - $total_devido) < 0.01) {
            echo "\nSTATUS: FINANCEIRO CORRETO (Pago coincide com Devido)\n";
        } else {
            echo "\nSTATUS: DISCREPÂNCIA DETECTADA! Diferença: " . ($pagos - $total_devido) . "\n";
        }
    } else {
        echo "\nNenhum registro financeiro (ID {$proc->financeiro_id}) encontrado para o procedimento {$proc->id}.\n";
    }
}
