<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Prescricao;

$req = ['start' => 0, 'length' => 6, 'search' => ['value' => '']];
$ret = Prescricao::index_pesq($req);

foreach ($ret['prescricoes'] as $p) {
    $aplicada = $p->get_semana_aplicada();
    $aplicar = $p->get_semana_aplicar();
    $edicao = $p->get_ultima_edicao();
    echo "#{$p->id} | " . ($p->paciente->nm_paciente ?? '?')
        . " | aplicada " . ($aplicada ? $aplicada->nr_semana . '/' . $p->qt_semanas : '-')
        . " | prox " . ($aplicar && $aplicar->data_prevista ? $aplicar->data_prevista : '-')
        . " | sit {$p->situacao}"
        . "\n";
}

echo "\n=== casos concluidos (ultimas 3) ===\n";
$concluidas = App\Models\Prescricao::with('paciente', 'semanas')->where('situacao', 'Concluída')->orderByDesc('id')->limit(3)->get();
foreach ($concluidas as $p) {
    $aplicada = $p->get_semana_aplicada();
    echo "#{$p->id} | " . ($p->paciente->nm_paciente ?? '?')
        . " | qt_semanas={$p->qt_semanas}"
        . " | aplicada " . ($aplicada ? $aplicada->nr_semana . '/' . $p->qt_semanas : '-')
        . "\n";
}
