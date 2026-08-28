<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrescricaoSemana;

$em = PrescricaoSemana::with('prescricao.paciente', 'medicamentos.medicamento')
    ->whereIn('situacao', ['Fila de Aplicação', 'Em Atendimento'])
    ->orderBy('dt_hr_chegada')->get();

$atendidos = PrescricaoSemana::with('prescricao.paciente', 'medicamentos.medicamento')
    ->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])
    ->whereDate('data_aplicada', date('Y-m-d'))
    ->orderByDesc('data_aplicada')->get();

echo "Em atendimento / fila: " . $em->count() . "\n";
foreach ($em as $s) {
    echo "  semana {$s->id} | " . ($s->prescricao->paciente->nm_paciente ?? '?') . " | {$s->situacao} | chegada " . ($s->dt_hr_chegada ?? '-') . "\n";
}

echo "\nAtendidos hoje (" . date('Y-m-d') . "): " . $atendidos->count() . "\n";
foreach ($atendidos as $s) {
    echo "  semana {$s->id} | " . ($s->prescricao->paciente->nm_paciente ?? '?') . " | {$s->situacao} | aplicada {$s->data_aplicada}\n";
}
