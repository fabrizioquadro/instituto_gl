<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Prescricao;

$p = Prescricao::with('paciente', 'clinica', 'semanas')->first();
echo "Prescricao {$p->id} | paciente: " . ($p->paciente->nm_paciente ?? '?') . " | clinica: " . ($p->clinica->nome ?? '?') . " | semanas: " . $p->semanas->count() . " | situacao: {$p->situacao}\n";
echo "Total prescricoes: " . Prescricao::count() . "\n";

// mostra 3 exemplos
$lista = Prescricao::with('paciente')->orderByDesc('data_prescricao')->limit(3)->get();
foreach ($lista as $pre) {
    echo "  #{$pre->id} | " . ($pre->paciente->nm_paciente ?? '?') . " | data {$pre->data_prescricao} | valor {$pre->valor_tratamento} | {$pre->situacao} | fin {$pre->situacao_financeira}\n";
}
