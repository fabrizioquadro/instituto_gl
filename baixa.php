<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$baixa = \App\Models\Baixa::create([
    'clinica_id' => 6,
    'user_id' => null,
    'motivo' => 'Solicitado via suporte / Correção de estoque',
    'data' => date('Y-m-d'),
    'valor' => 0,
]);

\App\Models\Estoque::create([
    'clinica_id' => 6,
    'baixa_id' => $baixa->id,
    'medicamento_id' => 11,
    'origem' => 'Baixa',
    'tipo' => 'Saida',
    'quantidade' => 32,
    'valor' => 0,
    'total' => 0,
    'lote' => '5365',
    'dt_vencimento' => '2026-07-10',
    'codigo_barras' => '1100005',
]);

echo "DONE\n";
