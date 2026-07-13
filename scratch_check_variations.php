<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$meds = App\Models\Medicamento::where('nome', 'LIKE', '%JARO%')
    ->orWhere('nome', 'LIKE', '%MOUN%')
    ->orWhere('nome', 'LIKE', '%MONJ%')
    ->get();
foreach ($meds as $med) {
    echo "ID: " . $med->id . " | NOME: " . $med->nome . " | UNIDADE: " . $med->unidade . " | VASILHAME: " . $med->vasilhame . " | GRUPO_ID: " . $med->grupo_id . "\n";
}
