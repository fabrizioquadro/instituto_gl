<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$med = App\Models\Medicamento::where('nome', 'LIKE', '%MOUNJARO 60MG%')->first();
if (!$med) {
    die("Medicamento não encontrado!\n");
}

$lots = ['3224', '3227', '3231', '3253', '000159', '000189'];
$clinicas = App\Models\Clinica::all();

foreach ($lots as $lot) {
    foreach ($clinicas as $clinica) {
        $barcodes = App\Models\Estoque::where('medicamento_id', $med->id)
            ->where('clinica_id', $clinica->id)
            ->where('lote', $lot)
            ->select('codigo_barras')
            ->distinct()
            ->get();
            
        foreach ($barcodes as $b) {
            $code = $b->codigo_barras;
            
            $entradas = App\Models\Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $med->id)
                ->where('lote', $lot)
                ->where('codigo_barras', $code)
                ->where('tipo', 'Entrada')
                ->sum('quantidade');
                
            $saidas = App\Models\Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $med->id)
                ->where('lote', $lot)
                ->where('codigo_barras', $code)
                ->where('tipo', 'Saida')
                ->sum('quantidade');
                
            $saldo = $entradas - $saidas;
            
            if ($saldo > 0) {
                $exemplo = App\Models\Estoque::where('clinica_id', $clinica->id)
                    ->where('medicamento_id', $med->id)
                    ->where('lote', $lot)
                    ->where('codigo_barras', $code)
                    ->first();
                    
                echo "Zerar estoque de: Clinica: {$clinica->nome} | Lote: $lot | Cod. Barras: $code | Saldo Atual: $saldo\n";
                
                App\Models\Estoque::create([
                    'clinica_id' => $clinica->id,
                    'medicamento_id' => $med->id,
                    'lote' => $lot,
                    'tipo' => 'Saida',
                    'quantidade' => $saldo,
                    'origem' => 'Ajuste de Estoque',
                    'valor' => $exemplo ? $exemplo->valor : 0,
                    'total' => $exemplo ? ($exemplo->valor * $saldo) : 0,
                    'dt_vencimento' => $exemplo ? $exemplo->dt_vencimento : null,
                    'codigo_barras' => $code,
                    'obs' => 'Ajuste manual para zerar lote vencido fisicamente inexistente',
                ]);
            }
        }
    }
}
echo "Ajuste concluído!\n";
