<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$codigo = '4500959';
$logs = App\Models\EstoqueAbertoLog::where('codigo_barras', $codigo)->get();

echo "### EstoqueAbertoLog ###\n";
foreach($logs as $l) {
    $proc = $l->procedimento_id ? App\Models\Procedimento::find($l->procedimento_id) : null;
    $pacienteNome = 'N/A';
    if ($proc) {
        $pac = App\Models\Paciente::find($proc->paciente_id);
        if ($pac) $pacienteNome = $pac->nome;
    }
    
    echo "Data: {$l->created_at} | Paciente: {$pacienteNome} | Qtd Usada: {$l->qt_utilizada} | Qtd Restante (depois): {$l->qt_restante}\n";
}
