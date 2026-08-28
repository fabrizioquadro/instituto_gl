<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ref = DB::table('migrations')->where('migration', 'like', '%fix_situacao_pendente%')->count();
echo "Referências órfãs na tabela migrations: $ref\n";

$sts = DB::table('prescricao_semanas')->selectRaw('situacao, count(*) as qt')->groupBy('situacao')->orderByDesc('qt')->get();
foreach ($sts as $s) {
    echo "  {$s->situacao}: {$s->qt}\n";
}
