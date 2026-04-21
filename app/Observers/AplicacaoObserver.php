<?php

namespace App\Observers;

use App\Models\Aplicacao;
use App\Models\ProcedimentoLog;
use App\Models\Medicamento;

class AplicacaoObserver
{
    public function created(Aplicacao $aplicacao): void
    {
        $med = Medicamento::find($aplicacao->medicamento_id);
        $nome_med = $med ? $med->nome : 'Medicamento ID: ' . $aplicacao->medicamento_id;
        
        ProcedimentoLog::registrar(
            $aplicacao->procedimento_id, 
            'Adição de Medicamento', 
            "Medicamento \"$nome_med\" adicionado (Quantidade: $aplicacao->quantidade).",
            null,
            ['medicamento' => $nome_med, 'quantidade' => $aplicacao->quantidade, 'total' => $aplicacao->total]
        );
    }

    public function updated(Aplicacao $aplicacao): void
    {
        $dirty = $aplicacao->getDirty();
        $original = $aplicacao->getOriginal();
        
        $med = Medicamento::find($aplicacao->medicamento_id);
        $nome_med = $med ? $med->nome : 'Medicamento';

        if (isset($dirty['situacao']) && $dirty['situacao'] == 'Aplicada') {
             ProcedimentoLog::registrar(
                $aplicacao->procedimento_id, 
                'Aplicação Realizada', 
                "O medicamento \"$nome_med\" foi aplicado com sucesso."
            );
        } else {
             // Registrar apenas campos relevantes para não poluir
             $relevantes = ['medicamento_id', 'quantidade', 'valor', 'total', 'obs'];
             $mudou = false;
             foreach($dirty as $key => $val) {
                 if(in_array($key, $relevantes)) $mudou = true;
             }

             if($mudou) {
                ProcedimentoLog::registrar(
                    $aplicacao->procedimento_id, 
                    'Alteração de Medicamento', 
                    "Dados do medicamento \"$nome_med\" foram editados.",
                    $original,
                    $dirty
                );
             }
        }
    }

    public function deleted(Aplicacao $aplicacao): void
    {
        $med = Medicamento::find($aplicacao->medicamento_id);
        $nome_med = $med ? $med->nome : 'Medicamento';

        ProcedimentoLog::registrar(
            $aplicacao->procedimento_id, 
            'Remoção de Medicamento', 
            "O medicamento \"$nome_med\" foi removido do procedimento."
        );
    }
}
