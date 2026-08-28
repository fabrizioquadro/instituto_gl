<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// situações atuais
$sts = DB::table('prescricao_semanas')->selectRaw('situacao, count(*) as qt')->groupBy('situacao')->orderByDesc('qt')->get();
echo "=== situações prescricao_semanas ===\n";
foreach ($sts as $s) echo "  {$s->situacao}: {$s->qt}\n";

// as que vieram de V1 Pendente
$pend = DB::table('prescricao_semanas as s')
    ->join('procedimentos as p', 'p.id', '=', 's.id_versao1')
    ->where('p.situacao', 'Pendente')
    ->selectRaw('s.situacao, count(*) as qt')
    ->groupBy('s.situacao')
    ->get();
echo "\n=== semanas com origem V1 Pendente ===\n";
foreach ($pend as $s) echo "  {$s->situacao}: {$s->qt}\n";

// amostra de algumas corrigidas
$amostra = DB::table('prescricao_semanas as s')
    ->join('procedimentos as p', 'p.id', '=', 's.id_versao1')
    ->where('p.situacao', 'Pendente')
    ->select('s.id', 's.nr_semana', 's.situacao', 's.data_aplicada', 'p.data_aplicacao')
    ->limit(5)
    ->get();
echo "\n=== amostra corrigida ===\n";
foreach ($amostra as $a) {
    echo "  semana {$a->id} | nr {$a->nr_semana} | {$a->situacao} | aplicada=" . ($a->data_aplicada ?? 'NULL') . " | v1.aplicacao={$a->data_aplicacao}\n";
}
