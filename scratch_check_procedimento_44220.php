<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;
use App\Models\Aplicacao;
use App\Models\ProcedimentoAnexo;
use App\Models\ProcedimentoLog;
use App\Models\ProcedimentoObservacao;
use App\Models\FinanceiroProcedimento;
use App\Models\Financeiro;

$procedimento_id = 44220;

$p = Procedimento::with('aplicacaos.medicamento', 'paciente')->find($procedimento_id);
if (!$p) {
    echo "Procedimento $procedimento_id NÃO ENCONTRADO.\n";
    exit;
}

echo "=== PROCEDIMENTO $procedimento_id ===\n";
echo "codigo      : {$p->codigo}\n";
echo "paciente    : {$p->paciente_id} (" . ($p->paciente->nome ?? '?') . ")\n";
echo "tipo        : {$p->tipo}\n";
echo "situacao    : {$p->situacao}\n";
echo "data        : {$p->data}\n";
echo "valor       : {$p->valor}\n";

echo "\n=== VÍNCULOS FINANCEIROS ===\n";
$pivots = FinanceiroProcedimento::where('procedimento_id', $p->id)->get();
if ($pivots->isEmpty()) {
    echo "Nenhum vínculo financeiro.\n";
} else {
    foreach ($pivots as $pv) {
        $fin = Financeiro::find($pv->financeiro_id);
        echo "  financeiro_id {$pv->financeiro_id}";
        if ($fin) {
            echo " (valor {$fin->valor}, situacao {$fin->situacao})";
            $outros = FinanceiroProcedimento::where('financeiro_id', $fin->id)
                ->where('procedimento_id', '!=', $p->id)->count();
            echo " | outros procedimentos vinculados ao financeiro: $outros";
        }
        echo "\n";
    }
}

echo "\n=== APLICAÇÕES ===\n";
$aps = $p->aplicacaos;
if ($aps->isEmpty()) {
    echo "Nenhuma aplicação.\n";
} else {
    foreach ($aps as $ap) {
        $medNome = $ap->medicamento->nome ?? '?';
        $medUni  = $ap->medicamento->unidade ?? '?';
        echo "  id {$ap->id} | situacao {$ap->situacao} | med $medNome ($medUni)\n";
    }
}

echo "\n=== ANEXOS ===\n";
echo "total: " . ProcedimentoAnexo::where('procedimento_id', $p->id)->count() . "\n";

echo "\n=== LOGS ===\n";
echo "total: " . ProcedimentoLog::where('procedimento_id', $p->id)->count() . "\n";

echo "\n=== OBSERVAÇÕES ===\n";
echo "total: " . ProcedimentoObservacao::where('procedimento_id', $p->id)->count() . "\n";
