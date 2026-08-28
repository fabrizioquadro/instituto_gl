<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;
use App\Models\Financeiro;
use App\Models\FinanceiroProcedimento;
use App\Models\FinanceiroFormasPagamento;

$proc = Procedimento::find(45077);

echo "========== GRUPO (codigo {$proc->codigo}) ==========\n";
$grupo = Procedimento::where('codigo', $proc->codigo)->orderBy('nr_procedimento')->get();
foreach ($grupo as $p) {
    echo "  - id {$p->id} | nr {$p->nr_procedimento} | situacao: {$p->situacao} | valor: {$p->valor} | st_pagamento: {$p->st_pagamento} | vl_pago: {$p->vl_pago}\n";
}

echo "\n========== FINANCEIRO 11646 ==========\n";
$fin = Financeiro::find(11646);
if ($fin) {
    echo json_encode($fin->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    $formas = FinanceiroFormasPagamento::where('financeiro_id', 11646)->get();
    echo "\nFormas de Pagamento:\n";
    foreach ($formas as $f) {
        echo "  - " . json_encode($f->toArray()) . "\n";
    }
    echo "\nProcedimentos vinculados a este financeiro:\n";
    $fins = FinanceiroProcedimento::where('financeiro_id', 11646)->get();
    foreach ($fins as $f) {
        $p = Procedimento::find($f->procedimento_id);
        echo "  - procedimento_id {$f->procedimento_id}" . ($p ? " | nr {$p->nr_procedimento} | situacao {$p->situacao} | valor {$p->valor}" : '') . "\n";
    }
} else {
    echo "Financeiro 11646 não encontrado.\n";
}
