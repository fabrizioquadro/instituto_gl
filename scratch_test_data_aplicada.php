<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use App\Models\PrescricaoSemana;

echo "colunas prescricao_semanas: " . implode(', ', Schema::getColumnListing('prescricao_semanas')) . "\n";

$s = PrescricaoSemana::first();
echo "semana {$s->nr_semana} | prevista=" . ($s->data_prevista ?? '-') . " | aplicada=" . ($s->data_aplicada ?? '(null)') . "\n";

// amostra de uma semana aplicada (mostra que data_aplicada está null nas antigas)
$aplicada = PrescricaoSemana::where('situacao', 'Aplicada')->first();
echo "semana aplicada {$aplicada->nr_semana} | aplicada=" . ($aplicada->data_aplicada ?? '(null)') . " | finalizacao=" . ($aplicada->dt_hr_finalizacao ?? '-') . "\n";
