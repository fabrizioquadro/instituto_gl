<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Prescricao;
use App\Models\PrescricaoSemana;
use App\Models\PrescricaoSemanaMedicamento;
use App\Models\Medicamento;

// Teste em transação com rollback (não persiste)
try {
    DB::beginTransaction();

    $prescricao = Prescricao::whereHas('semanas')->first();
    $medicamento = Medicamento::first();

    echo "Prescricao #{$prescricao->id} | qt_semanas antes: {$prescricao->qt_semanas}\n";

    // simula insert_semana
    $nr = $prescricao->semanas()->count() + 1;
    $semana = PrescricaoSemana::create([
        'prescricao_id' => $prescricao->id,
        'nr_semana' => $nr,
        'data_prevista' => date('Y-m-d'),
        'data_aplicada' => null,
        'tem_aplicacao' => false,
        'situacao' => 'Agendada',
        'obs' => 'TESTE AUTO',
    ]);
    echo "Semana criada nr {$semana->nr_semana} id {$semana->id}\n";

    // simula inserir_medicamentos_semana
    PrescricaoSemanaMedicamento::create([
        'prescricao_semana_id' => $semana->id,
        'medicamento_id' => $medicamento->id,
        'combo_id' => null,
        'is_soro' => str_starts_with(strtolower($medicamento->nome), 'soro'),
        'gera_aplicacao' => $medicamento->aplicacao == 'Sim',
        'quantidade' => 2,
        'situacao' => 'Aberta',
        'data_prevista' => $semana->data_prevista,
    ]);
    echo "Medicacao adicionada: {$medicamento->nome} (aplicacao={$medicamento->aplicacao})\n";

    // recalcular_tem_aplicacao
    $semana->tem_aplicacao = $semana->medicamentos()->where('gera_aplicacao', true)->exists();
    $semana->save();
    echo "tem_aplicacao da semana: " . ($semana->tem_aplicacao ? 'true' : 'false') . "\n";

    // recalcular_semanas
    $semanas = $prescricao->semanas()->orderBy('nr_semana')->get();
    $i = 1;
    foreach ($semanas as $s) { $s->nr_semana = $i; $s->save(); $i++; }
    $prescricao->qt_semanas = $semanas->count();
    $prescricao->qt_semanas_aplicacao = $prescricao->semanas()->where('tem_aplicacao', true)->count();
    $prescricao->save();
    echo "qt_semanas depois: {$prescricao->qt_semanas} | qt_semanas_aplicacao: {$prescricao->qt_semanas_aplicacao}\n";

    // simula delete_semana (limpeza)
    foreach ($semana->medicamentos as $med) {
        $med->delete();
    }
    $semana->delete();
    echo "Semana de teste excluída (rollback aplicará igualmente)\n";

    DB::rollBack();
    echo "\n✅ OK - todas as operações de semana/medicação funcionaram (dados revertidos)\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
