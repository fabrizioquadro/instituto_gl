<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = App\Models\Estoque::where('origem', 'LIKE', 'Ajuste%')->get();
echo "Total Ajustes: " . $entries->count() . "\n";
$distinct_origens = App\Models\Estoque::select('origem')->distinct()->get();
echo "\nDistinct origens in table:\n";
foreach ($distinct_origens as $o) {
    echo "- '" . $o->origem . "'\n";
}

if ($entries->count() > 0) {
    echo "\nSample adjustments:\n";
    foreach ($entries->take(10) as $e) {
        $medName = $e->medicamento ? $e->medicamento->nome : $e->medicamento_id;
        $clinicaName = $e->clinica ? $e->clinica->nome : $e->clinica_id;
        echo "ID: {$e->id} | Clinica: $clinicaName | Med: $medName | Qtd: {$e->quantidade} | Tipo: {$e->tipo} | Lote: {$e->lote} | Origem: {$e->origem} | Created: {$e->created_at}\n";
    }
}
