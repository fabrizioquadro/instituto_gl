<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ids = [
    34394, // Lote 3224 - Entrada GL
    34397, // Lote 3227 - Entrada GL
    34000, // Lote 3224 - Entrada Tatuape
    34420, // Lote 000189 - Entrada Estoque Central
    34421, // Lote 000159 - Entrada GL
];

foreach ($ids as $id) {
    $e = App\Models\Estoque::find($id);
    if ($e) {
        echo "ID: $id\n";
        echo "  Medicamento: " . ($e->medicamento ? $e->medicamento->nome : $e->medicamento_id) . "\n";
        echo "  Clinica: " . ($e->clinica ? $e->clinica->nome : $e->clinica_id) . "\n";
        echo "  Lote: " . $e->lote . "\n";
        echo "  Tipo: " . $e->tipo . "\n";
        echo "  Quantidade: " . $e->quantidade . "\n";
        echo "  Vencimento: " . $e->dt_vencimento . "\n";
        echo "  Criado: " . $e->created_at . "\n";
        echo "  Obs: " . $e->obs . "\n";
        echo "  User ID: " . $e->user_id_cadastro . "\n";
        
        // Let's check if there is a transfer record associated or if it was a manual entrance
        // We can search for other stocks created at the exact same second to see if it was a transfer (Saida from Central, Entrada in GL)
        $simultaneous = App\Models\Estoque::where('created_at', $e->created_at)->get();
        if ($simultaneous->count() > 1) {
            echo "  Registros simultaneos (Transferencia?):\n";
            foreach ($simultaneous as $s) {
                if ($s->id != $e->id) {
                    echo "    - ID: {$s->id} | Clinica: " . ($s->clinica ? $s->clinica->nome : $s->clinica_id) . " | Tipo: {$s->tipo} | Qtd: {$s->quantidade} | Lote: {$s->lote}\n";
                }
            }
        }
    } else {
        echo "ID: $id NOT FOUND\n";
    }
    echo "\n";
}
