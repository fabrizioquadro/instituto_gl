<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;

// Data de ontem
$ontem = date('Y-m-d', strtotime('-1 day'));
echo "Analisando atendimentos do dia: " . $ontem . "\n\n";

$procedimentos_aplicadas = Procedimento::where('situacao','Aplicado')
    ->where('data_aplicacao', $ontem)
    ->get();

$resumo_enfermeiras = [];
$total_pacientes = 0;
$total_aplicacao = 0;
$total_bio = 0;

foreach($procedimentos_aplicadas as $proc){
    $nome = $proc->aplicadora ? $proc->aplicadora->nome : 'Não Identificada';
    if(!isset($resumo_enfermeiras[$nome])){
        $resumo_enfermeiras[$nome] = [
            'pacientes' => 0,
            'aplicacao' => 0,
            'bio' => 0,
            'ids_nao_identificados' => []
        ];
    }
    $resumo_enfermeiras[$nome]['pacientes']++;
    $total_pacientes++;

    if($nome == 'Não Identificada') {
        $resumo_enfermeiras[$nome]['ids_nao_identificados'][] = $proc->id;
    }

    if($proc->aplicacaos->count() > 0){
        $resumo_enfermeiras[$nome]['aplicacao']++;
        $total_aplicacao++;
    }
    if($proc->st_biopedancia == 'Sim'){
        $resumo_enfermeiras[$nome]['bio']++;
        $total_bio++;
    }
}

ksort($resumo_enfermeiras);

echo "Enfermeira | Qtd Pacientes | Qtd Aplicacao | Qtd Bio\n";
echo str_repeat("-", 60) . "\n";
foreach($resumo_enfermeiras as $enfermeira => $dados) {
    echo str_pad($enfermeira, 20) . " | " . 
         str_pad($dados['pacientes'], 13) . " | " . 
         str_pad($dados['aplicacao'], 13) . " | " . 
         $dados['bio'] . "\n";
}

echo str_repeat("-", 60) . "\n";
echo str_pad("TOTAL GERAL", 20) . " | " . 
     str_pad($total_pacientes, 13) . " | " . 
     str_pad($total_aplicacao, 13) . " | " . 
     $total_bio . "\n\n";

if(isset($resumo_enfermeiras['Não Identificada']) && count($resumo_enfermeiras['Não Identificada']['ids_nao_identificados']) > 0) {
    echo "Detalhes dos procedimentos 'Não Identificada':\n";
    foreach($resumo_enfermeiras['Não Identificada']['ids_nao_identificados'] as $id) {
        $proc = Procedimento::find($id);
        echo "- Procedimento ID: " . $id . " | Paciente: " . ($proc->paciente ? $proc->paciente->nm_paciente : 'N/A') . " | User ID Aplicacao: " . ($proc->user_id_aplicacao ?? 'NULL') . "\n";
    }
}
