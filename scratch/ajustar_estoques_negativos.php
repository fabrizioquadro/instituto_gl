<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Estoque;
use App\Models\Clinica;
use App\Models\Medicamento;

echo "--- INICIANDO AJUSTE DE ESTOQUES NEGATIVOS ---\n\n";

DB::beginTransaction();

try {
    // 1. Buscar todos os lotes que possuem saldo negativo
    $resultados = DB::select("
        SELECT 
            clinica_id, 
            medicamento_id, 
            lote,
            (SUM(CASE WHEN tipo = 'Entrada' THEN quantidade ELSE 0 END) - SUM(CASE WHEN tipo = 'Saida' THEN quantidade ELSE 0 END)) as saldo
        FROM estoques
        GROUP BY clinica_id, medicamento_id, lote
        HAVING saldo < 0
    ");

    if (empty($resultados)) {
        echo "Nenhum estoque negativo encontrado para ajustar!\n";
        DB::rollBack();
        exit;
    }

    echo "Encontrados " . count($resultados) . " lotes negativos para ajustar.\n";
    echo "Inserindo lançamentos de entrada para zerar o saldo...\n\n";

    $ajustesRealizados = 0;

    foreach ($resultados as $res) {
        $saldoNegativo = (float)$res->saldo;
        $quantidadeAjuste = abs($saldoNegativo);

        // Buscar um lançamento existente desse mesmo lote para clonar metadados úteis
        $exemplo = Estoque::where('clinica_id', $res->clinica_id)
            ->where('lote', $res->lote)
            ->where(function($query) use ($res) {
                if (is_null($res->medicamento_id)) {
                    $query->whereNull('medicamento_id');
                } else {
                    $query->where('medicamento_id', $res->medicamento_id);
                }
            })
            ->first();

        // Nomes amigáveis para o log
        $clinica = Clinica::find($res->clinica_id);
        $medicamento = Medicamento::find($res->medicamento_id);

        $nomeClinica = $clinica ? $clinica->nome : "Clínica ID: " . $res->clinica_id;
        $nomeMedicamento = $medicamento ? $medicamento->nome : "Medicamento não identificado";
        $loteExibicao = $res->lote ?? '[Sem Lote]';

        echo "Ajustando: Unidade [{$nomeClinica}] | Medicamento [{$nomeMedicamento}] | Lote [{$loteExibicao}] | Saldo: {$saldoNegativo} -> Novo Lançamento: +{$quantidadeAjuste}\n";

        // Inserir registro de entrada corretivo
        Estoque::create([
            'clinica_id' => $res->clinica_id,
            'medicamento_id' => $res->medicamento_id,
            'lote' => $res->lote,
            'tipo' => 'Entrada',
            'quantidade' => $quantidadeAjuste,
            'origem' => 'Ajuste de Estoque Negativo',
            'valor' => $exemplo ? (float)$exemplo->valor : 0.0,
            'total' => $exemplo ? ((float)$exemplo->valor * $quantidadeAjuste) : 0.0,
            'dt_vencimento' => $exemplo ? $exemplo->dt_vencimento : null,
            'codigo_barras' => $exemplo ? $exemplo->codigo_barras : null,
            'entrada_id' => null,
            'baixa_id' => null,
            'transferencia_id' => null,
            'procedimento_id' => null,
        ]);

        $ajustesRealizados++;
    }

    DB::commit();
    echo "\nSucesso! {$ajustesRealizados} registros de ajuste de estoque criados e consolidados no banco de dados.\n\n";

    // 2. Chamar a regeneração do JSON e HTML para refletir que não há mais estoques negativos
    echo "Regenerando os relatórios...\n";
    
    // Regenerar JSON
    $resultadosAtualizados = DB::select("
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

    $relatorioAtualizado = [];
    foreach ($resultadosAtualizados as $res) {
        $clinica = Clinica::find($res->clinica_id);
        $medicamento = Medicamento::find($res->medicamento_id);
        
        $nomeClinica = $clinica ? $clinica->nome : "Clínica ID: " . $res->clinica_id;
        $nomeMedicamento = $medicamento ? $medicamento->nome : "Medicamento ID: " . $res->medicamento_id;
        $fabricante = $medicamento ? $medicamento->fabricante : "N/D";
        
        $relatorioAtualizado[$nomeClinica][] = [
            'medicamento_id' => $res->medicamento_id,
            'medicamento' => $nomeMedicamento,
            'fabricante' => $fabricante,
            'lote' => $res->lote ?? '[Sem Lote]',
            'total_entrada' => (float)$res->total_entrada,
            'total_saida' => (float)$res->total_saida,
            'saldo' => (float)$res->saldo
        ];
    }

    file_put_contents(__DIR__ . '/relatorio_saldo_negativo.json', json_encode($relatorioAtualizado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "JSON atualizado.\n";

    // Regenerar HTML
    echo "Executando o gerador de HTML...\n";
    @include __DIR__ . '/gerar_html.php';
    echo "HTML atualizado.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Erro crítico ao processar ajustes: " . $e->getMessage() . "\n";
}
