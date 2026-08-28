<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$semanas = DB::table('prescricao_semanas')
    ->where('situacao', 'Em Atendimento')
    ->whereNull('dt_hr_chegada')
    ->get(['id', 'id_versao1', 'prescricao_id', 'nr_semana', 'situacao', 'dt_hr_chegada', 'dt_hr_atendimento', 'created_at']);

echo "Total semanas 'Em Atendimento' sem chegada: " . $semanas->count() . "\n\n";

$comChegadaV1 = 0;
$semChegadaV1 = 0;
$v1naoEncontrado = 0;
$amostras = [];

foreach ($semanas as $s) {
    $v1 = DB::table('procedimentos')->where('id', $s->id_versao1)->first(['id', 'situacao', 'dt_hr_chegada', 'dt_hr_atendimento', 'data_cad', 'nr_procedimento']);
    if (!$v1) {
        $v1naoEncontrado++;
        $amostras[] = "semana {$s->id} (v1 {$s->id_versao1}) -> V1 NÃO ENCONTRADA";
        continue;
    }
    if ($v1->dt_hr_chegada) {
        $comChegadaV1++;
        $amostras[] = "semana {$s->id} (v1 {$s->id_versao1}) -> V1 SITUACAO={$v1->situacao} chegada={$v1->dt_hr_chegada} (TEM chegada na V1!)";
    } else {
        $semChegadaV1++;
        $amostras[] = "semana {$s->id} (v1 {$s->id_versao1}) -> V1 SITUACAO={$v1->situacao} chegada=NULL atendimento=" . ($v1->dt_hr_atendimento ?? 'NULL') . " data_cad={$v1->data_cad}";
    }
}

echo "V1 com chegada preenchida: $comChegadaV1\n";
echo "V1 com chegada NULL: $semChegadaV1\n";
echo "V1 não encontrada: $v1naoEncontrado\n\n";

echo "=== amostras ===\n";
foreach ($amostras as $a) {
    echo "  $a\n";
}
