<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrescricaoSemana;

$semana = PrescricaoSemana::with('prescricao.paciente')->find(210583);
if (!$semana) {
    echo "Semana 210583 NÃO ENCONTRADA.\n";
    exit;
}
echo "Semana 210583 | prescricao_id={$semana->prescricao_id} | nr={$semana->nr_semana} | situacao={$semana->situacao}\n";
echo "Paciente: " . ($semana->prescricao->paciente->nm_paciente ?? '?') . "\n";

$semanas = $semana->prescricao->semanas()->with('medicamentos.medicamento')->get();
echo "\nTotal de semanas da prescrição: " . $semanas->count() . "\n";
foreach ($semanas as $s) {
    $atual = $s->id == $semana->id ? '  <-- atual' : '';
    echo "  semana {$s->nr_semana} | id {$s->id} | {$s->situacao} | prevista " . ($s->data_prevista ?? '-') . " | meds " . $s->medicamentos->count() . "{$atual}\n";
}
