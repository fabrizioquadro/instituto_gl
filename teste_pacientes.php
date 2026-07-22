<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$codigo = '4500959';
$aplicacoesLote = App\Models\AplicacaoLote::where('codigo_barras', $codigo)->get();

echo "### Aplicacoes e Pacientes ###\n";
foreach($aplicacoesLote as $al) {
    if ($al->aplicacao_id) {
        $aplicacao = App\Models\Aplicacao::find($al->aplicacao_id);
        if ($aplicacao) {
            $proc = App\Models\Procedimento::find($aplicacao->procedimento_id);
            if ($proc) {
                $paciente = App\Models\Paciente::find($proc->paciente_id);
                $hora = $al->created_at->format('H:i');
                echo "- {$hora} | Qtd: {$al->quantidade} | Paciente: " . ($paciente ? $paciente->nm_paciente : 'Desconhecido') . "\n";
            }
        }
    }
}
