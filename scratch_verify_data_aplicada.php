<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tot = DB::table('prescricao_semanas')->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])->count();
$preenchidas = DB::table('prescricao_semanas')->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])->whereNotNull('data_aplicada')->count();
$semData = $tot - $preenchidas;

echo "Semanas aplicadas/parciais: $tot\n";
echo "Com data_aplicada preenchida: $preenchidas\n";
echo "Sem data (null): $semData\n\n";

// amostra
$rows = DB::table('prescricao_semanas')
    ->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])
    ->limit(5)
    ->get(['id', 'nr_semana', 'situacao', 'data_prevista', 'data_aplicada']);
foreach ($rows as $r) {
    echo "semana {$r->nr_semana} | {$r->situacao} | prevista {$r->data_prevista} | aplicada {$r->data_aplicada}\n";
}
