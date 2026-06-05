<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;

$ontem = date('Y-m-d', strtotime('-1 day'));

$procedimentos_nao_identificados = Procedimento::where('situacao','Aplicado')
    ->where('data_aplicacao', $ontem)
    ->whereNull('user_id_aplicacao')
    ->get();

echo "Verificando os medicamentos das 'Nao Identificadas'...\n";
foreach($procedimentos_nao_identificados as $p) {
    $meds = [];
    foreach($p->aplicacaos as $ap) {
        $nome = $ap->medicamento ? $ap->medicamento->nome : 'ID '.$ap->medicamento_id;
        $meds[] = $nome;
    }
    echo "Proc ID {$p->id} (Tipo: {$p->tipo_atendimento}) -> Medicamentos: " . implode(', ', $meds) . "\n";
}
