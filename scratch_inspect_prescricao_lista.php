<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// distribuição de semana_atual
$sems = DB::table('prescricaos')->selectRaw('semana_atual, count(*) as qt')->groupBy('semana_atual')->orderBy('semana_atual')->get();
echo "=== semana_atual (prescricaos) ===\n";
foreach ($sems as $s) {
    echo "  semana_atual={$s->semana_atual}: {$s->qt}\n";
}

// situações das semanas
$sts = DB::table('prescricao_semanas')->selectRaw('situacao, count(*) as qt')->groupBy('situacao')->orderByDesc('qt')->get();
echo "\n=== situacao (prescricao_semanas) ===\n";
foreach ($sts as $s) {
    echo "  {$s->situacao}: {$s->qt}\n";
}

// acoes dos logs
$acs = DB::table('prescricao_logs')->selectRaw('acao, count(*) as qt')->groupBy('acao')->orderByDesc('qt')->limit(20)->get();
echo "\n=== acao (prescricao_logs) ===\n";
foreach ($acs as $s) {
    echo "  {$s->acao}: {$s->qt}\n";
}

// entidade dos logs
$ents = DB::table('prescricao_logs')->selectRaw('entidade, count(*) as qt')->groupBy('entidade')->orderByDesc('qt')->limit(10)->get();
echo "\n=== entidade (prescricao_logs) ===\n";
foreach ($ents as $s) {
    echo "  {$s->entidade}: {$s->qt}\n";
}

// amostra: prescricao com semanas
echo "\n=== amostra prescricao + semanas ===\n";
$pres = DB::table('prescricaos')->where('qt_semanas', '>', 3)->orderByDesc('id')->limit(2)->get();
foreach ($pres as $p) {
    echo "  Prescricao #{$p->id} | qt_semanas={$p->qt_semanas} | semana_atual={$p->semana_atual} | situacao={$p->situacao}\n";
    $sems2 = DB::table('prescricao_semanas')->where('prescricao_id', $p->id)->orderBy('nr_semana')->get();
    foreach ($sems2 as $s) {
        echo "      semana {$s->nr_semana} | {$s->situacao} | tem_aplicacao={$s->tem_aplicacao} | prevista {$s->data_prevista}\n";
    }
}

// entidades distintas
$ents2 = DB::table('prescricao_logs')->selectRaw('entidade, count(*) as qt')->groupBy('entidade')->get();
echo "\n=== entidade (todas) ===\n";
foreach ($ents2 as $s) {
    echo "  {$s->entidade}: {$s->qt}\n";
}
