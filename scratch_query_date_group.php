<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = App\Models\Estoque::where('created_at', 'LIKE', '2026-05-20 12:36%')
    ->get();

echo "Total records at 2026-05-20 12:36 : " . $entries->count() . "\n\n";

// Let's count them by clinica and lote and tipo:
$counts = [];
foreach ($entries as $e) {
    $medName = $e->medicamento ? $e->medicamento->nome : $e->medicamento_id;
    $clinicaName = $e->clinica ? $e->clinica->nome : $e->clinica_id;
    $key = "$clinicaName | $medName | Lote: $e->lote | $e->tipo";
    if (!isset($counts[$key])) {
        $counts[$key] = 0;
    }
    $counts[$key] += $e->quantidade;
}

foreach ($counts as $k => $c) {
    echo "$k : Qty $c\n";
}

echo "\n--- SAMPLE RECORD ---\n";
if ($entries->count() > 0) {
    $sample = $entries->first();
    print_r($sample->toArray());
}
