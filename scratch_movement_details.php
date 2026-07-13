<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$med = App\Models\Medicamento::where('nome', 'LIKE', '%MOUNJARO 60MG%')->first();
$lots = ['3224', '3227', '3231', '3253', '000159', '000189'];
$clinicas = App\Models\Clinica::all();

foreach ($lots as $lot) {
    echo "========================================================\n";
    echo "LOTE: $lot\n";
    echo "========================================================\n";
    
    foreach ($clinicas as $clinica) {
        $entradas = App\Models\Estoque::where('clinica_id', $clinica->id)
            ->where('medicamento_id', $med->id)
            ->where('lote', $lot)
            ->where('tipo', 'Entrada')
            ->sum('quantidade');
            
        $saidas = App\Models\Estoque::where('clinica_id', $clinica->id)
            ->where('medicamento_id', $med->id)
            ->where('lote', $lot)
            ->where('tipo', 'Saida')
            ->sum('quantidade');
            
        $saldo = $entradas - $saidas;
        
        if ($saldo > 0) {
            echo "Clinica: " . $clinica->nome . " (ID: " . $clinica->id . ")\n";
            echo "  Entradas: $entradas | Saidas: $saidas | SALDO: $saldo\n";
            echo "  Movimentacoes:\n";
            
            $movs = App\Models\Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $med->id)
                ->where('lote', $lot)
                ->orderBy('created_at')
                ->get();
                
            foreach ($movs as $m) {
                // If there's an application associated or other details:
                // Let's print the fields: tipo, quantidade, created_at, user_id, obs, etc.
                echo "    - ID: {$m->id} | Tipo: {$m->tipo} | Qtd: {$m->quantidade} | Venc: {$m->dt_vencimento} | Data: {$m->created_at} | Obs: {$m->obs}\n";
            }
        }
    }
    echo "\n";
}
