<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Clinica;
use App\Models\Medicamento;

try {
    $resultados = DB::select("
        SELECT 
            clinica_id, 
            medicamento_id, 
            lote,
            SUM(CASE WHEN tipo = 'Entrada' THEN quantidade ELSE 0 END) as total_entrada,
            SUM(CASE WHEN tipo = 'Saida' THEN quantidade ELSE 0 END) as total_saida,
            (SUM(CASE WHEN tipo = 'Entrada' THEN quantidade ELSE 0 END) - SUM(CASE WHEN tipo = 'Saida' THEN quantidade ELSE 0 END)) as saldo
        FROM estoques
        GROUP BY clinica_id, medicamento_id, lote
        HAVING saldo < 0
    ");

    $relatorio = [];
    foreach ($resultados as $res) {
        $clinica = Clinica::find($res->clinica_id);
        $medicamento = Medicamento::find($res->medicamento_id);
        
        $nomeClinica = $clinica ? $clinica->nome : "Clínica ID: " . $res->clinica_id;
        $nomeMedicamento = $medicamento ? $medicamento->nome : "Medicamento ID: " . $res->medicamento_id;
        $fabricante = $medicamento ? $medicamento->fabricante : "N/D";
        
        $relatorio[$nomeClinica][] = [
            'medicamento_id' => $res->medicamento_id,
            'medicamento' => $nomeMedicamento,
            'fabricante' => $fabricante,
            'lote' => $res->lote ?? '[Sem Lote]',
            'total_entrada' => (float)$res->total_entrada,
            'total_saida' => (float)$res->total_saida,
            'saldo' => (float)$res->saldo
        ];
    }

    $jsonPath = __DIR__ . '/relatorio_saldo_negativo.json';
    file_put_contents($jsonPath, json_encode($relatorio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "RELATORIO_GERADO_SUCESSO\n";

} catch (\Exception $e) {
    echo "Erro ao executar query: " . $e->getMessage() . "\n";
}
