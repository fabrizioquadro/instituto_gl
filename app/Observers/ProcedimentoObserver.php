<?php

namespace App\Observers;

use App\Models\Procedimento;
use App\Models\ProcedimentoLog;

class ProcedimentoObserver
{
    public function created(Procedimento $procedimento): void
    {
        ProcedimentoLog::registrar($procedimento->id, 'Criação', 'Procedimento criado no sistema.');
    }

    public function updated(Procedimento $procedimento): void
    {
        $dirty = $procedimento->getDirty();
        $original = $procedimento->getOriginal();

        $importantes = ['medico', 'data_aplicacao', 'agendamento', 'situacao', 'valor', 'st_pagamento', 'vl_pago', 'data_cad', 'obs', 'st_biopedancia', 'st_coleta', 'st_retirada'];
        $dados_antigos = [];
        $dados_novos = [];
        $mudou = false;

        foreach ($dirty as $key => $value) {
            if (in_array($key, $importantes)) {
                $mudou = true;
                $dados_antigos[$key] = $original[$key] ?? 'Nulo';
                $dados_novos[$key] = $value;
            }
        }

        if ($mudou) {
            ProcedimentoLog::registrar(
                $procedimento->id, 
                'Atualização', 
                'Alteração nos dados principais do procedimento.', 
                $dados_antigos, 
                $dados_novos
            );
        }
    }

    public function deleted(Procedimento $procedimento): void
    {
        ProcedimentoLog::registrar($procedimento->id, 'Exclusão', 'Procedimento removido do sistema.');
    }
}
