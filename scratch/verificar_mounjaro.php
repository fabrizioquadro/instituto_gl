<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Clinica;
use App\Models\Medicamento;

try {
    // 1. Encontrar todos os medicamentos relacionados a MOUNJARO
    $medicamentos = Medicamento::where('nome', 'LIKE', '%MOUNJARO%')->get();
    
    echo "=== MEDICAMENTOS ENCONTRADOS ===\n";
    foreach ($medicamentos as $med) {
        echo "ID: {$med->id} | Nome: {$med->nome} | Fabricante: {$med->fabricante}\n";
    }
    echo "=================================\n\n";

    // 2. Buscar o estoque detalhado para cada lote/clínica desses medicamentos
    $medIds = $medicamentos->pluck('id')->toArray();
    
    if (empty($medIds)) {
        echo "Nenhum medicamento 'MOUNJARO' encontrado no cadastro.\n";
        exit;
    }

    $resultados = DB::table('estoques')
        ->select(
            'clinica_id',
            'medicamento_id',
            'lote',
            DB::raw("SUM(CASE WHEN tipo = 'Entrada' THEN quantidade ELSE 0 END) as total_entrada"),
            DB::raw("SUM(CASE WHEN tipo = 'Saida' THEN quantidade ELSE 0 END) as total_saida"),
            DB::raw("(SUM(CASE WHEN tipo = 'Entrada' THEN quantidade ELSE 0 END) - SUM(CASE WHEN tipo = 'Saida' THEN quantidade ELSE 0 END)) as saldo")
        )
        ->whereIn('medicamento_id', $medIds)
        ->groupBy('clinica_id', 'medicamento_id', 'lote')
        ->orderBy('clinica_id')
        ->orderBy('medicamento_id')
        ->orderBy('lote')
        ->get();

    $relatorio = [];
    foreach ($resultados as $res) {
        $clinica = Clinica::find($res->clinica_id);
        $medicamento = Medicamento::find($res->medicamento_id);
        
        $nomeClinica = $clinica ? $clinica->nome : "Clínica ID: " . $res->clinica_id;
        $nomeMedicamento = $medicamento ? $medicamento->nome : "Medicamento ID: " . $res->medicamento_id;
        $fabricante = $medicamento ? $medicamento->fabricante : "N/D";
        
        $relatorio[] = [
            'clinica_id' => $res->clinica_id,
            'clinica' => $nomeClinica,
            'medicamento_id' => $res->medicamento_id,
            'medicamento' => $nomeMedicamento,
            'fabricante' => $fabricante,
            'lote' => $res->lote ?? '[Sem Lote]',
            'total_entrada' => (float)$res->total_entrada,
            'total_saida' => (float)$res->total_saida,
            'saldo' => (float)$res->saldo
        ];
    }

    echo json_encode($relatorio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
