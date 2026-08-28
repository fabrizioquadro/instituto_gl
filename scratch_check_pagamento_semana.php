<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrescricaoSemana;
use App\Models\FinanceiroParcela;

$semana = PrescricaoSemana::find(210583);
$parcela = FinanceiroParcela::where('prescricao_semana_id', $semana->id)->first();

$total = $parcela->valor_pago ?? 0;
$valor = $parcela->valor_parcela ?? 0;
$paga = $total >= $valor && $valor > 0;

echo "Semana {$semana->id} | situacao={$semana->situacao}\n";
echo "Parcela: " . ($parcela ? "valor_parcela={$parcela->valor_parcela} | valor_pago={$parcela->valor_pago} | situacao={$parcela->situacao}" : 'SEM PARCELA') . "\n";
echo "Considerada paga? " . ($paga ? 'SIM' : 'NÃO') . "\n";
