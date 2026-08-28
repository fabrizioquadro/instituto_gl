<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Quantas semanas aplicadas/parciais
$tot = DB::table('prescricao_semanas')->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])->count();
echo "semanas Aplicada/Aplicação Parcial: $tot\n";

// Quantas têm dt_hr_finalizacao / dt_hr_atendimento preenchidos
$fin = DB::table('prescricao_semanas')->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])->whereNotNull('dt_hr_finalizacao')->count();
$ate = DB::table('prescricao_semanas')->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])->whereNotNull('dt_hr_atendimento')->count();
echo "com dt_hr_finalizacao: $fin\n";
echo "com dt_hr_atendimento: $ate\n";

// Quantas têm medicamentos com aplicado_em
$med = DB::table('prescricao_semana_medicamentos')
    ->join('prescricao_semanas', 'prescricao_semana_medicamentos.prescricao_semana_id', '=', 'prescricao_semanas.id')
    ->whereIn('prescricao_semanas.situacao', ['Aplicada', 'Aplicação Parcial'])
    ->whereNotNull('prescricao_semana_medicamentos.aplicado_em')
    ->distinct('prescricao_semana_medicamentos.prescricao_semana_id')
    ->count();
echo "semanas aplicadas com medicamento aplicado_em: $med\n";

// amostra
echo "\n=== amostra ===\n";
$rows = DB::table('prescricao_semanas')
    ->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])
    ->limit(5)
    ->get(['id', 'id_versao1', 'nr_semana', 'situacao', 'data_prevista', 'data_aplicada', 'dt_hr_atendimento', 'dt_hr_finalizacao']);
foreach ($rows as $r) {
    $v1 = DB::table('procedimentos')->where('id', $r->id_versao1)->first(['data_aplicacao', 'data_pagamento']);
    $ap = DB::table('prescricao_semana_medicamentos')->where('prescricao_semana_id', $r->id)->whereNotNull('aplicado_em')->first(['aplicado_em']);
    echo "semana {$r->id} (v1 {$r->id_versao1}) | nr {$r->nr_semana} | {$r->situacao}\n";
    echo "   v1.data_aplicacao=" . ($v1->data_aplicacao ?? 'null') . " | v1.data_pagamento=" . ($v1->data_pagamento ?? 'null') . "\n";
    echo "   dt_hr_atendimento=" . ($r->dt_hr_atendimento ?? 'null') . " | dt_hr_finalizacao=" . ($r->dt_hr_finalizacao ?? 'null') . "\n";
    echo "   medicamento.aplicado_em=" . ($ap->aplicado_em ?? 'null') . "\n";
}
