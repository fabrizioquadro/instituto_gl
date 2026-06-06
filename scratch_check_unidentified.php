<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hoje = date('Y-m-d');
$procedimentos_aplicadas = \App\Models\Procedimento::where('situacao','Aplicado')
    ->where('data_aplicacao', $hoje)
    ->get();

foreach($procedimentos_aplicadas as $proc){
    if(str_contains($proc->tipo_atendimento, 'Consulta') || str_contains($proc->tipo_atendimento, 'Retorno')) {
        continue;
    }
    if(!$proc->user_id_aplicacao){
        echo "Procedimento ID: " . $proc->id . " - Paciente: " . ($proc->paciente ? $proc->paciente->nm_paciente : 'Sem Paciente') . "\n";
    }
}
