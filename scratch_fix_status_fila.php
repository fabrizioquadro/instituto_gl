<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrescricaoSemana;

// Corrige apenas a semana que o usuário testou (se ficou com status errado).
$id = 210583;
$s = PrescricaoSemana::find($id);
if (!$s) {
    echo "Semana $id não encontrada.\n";
    exit;
}
echo "Semana $id antes: situacao={$s->situacao} | chegada=" . ($s->dt_hr_chegada ?? '-') . "\n";

if ($s->situacao == 'Em Atendimento') {
    $s->situacao = 'Fila de Aplicação';
    $s->save();
    echo "Semana $id corrigida para 'Fila de Aplicação'.\n";
} else {
    echo "Semana $id já está correta (não mexido).\n";
}

// apenas informa quantas outras semanas 'Em Atendimento' existem (não mexe)
$tot = PrescricaoSemana::where('situacao', 'Em Atendimento')->count();
echo "Total de semanas 'Em Atendimento' no banco (dados migrados/não mexidas): $tot\n";
