<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Medicamento;

$ids = [45, 50, 27, 26, 32];
foreach ($ids as $id) {
    $m = Medicamento::find($id);
    if ($m) {
        echo "ID {$id} | {$m->nome} | aplicacao '{$m->aplicacao}' | unidade '{$m->unidade}'\n";
    } else {
        echo "ID {$id} | NAO ENCONTRADO\n";
    }
}
