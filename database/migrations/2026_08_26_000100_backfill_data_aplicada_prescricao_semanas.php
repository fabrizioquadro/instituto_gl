<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: preenche prescricao_semanas.data_aplicada nas semanas já aplicadas,
 * usando a data que tínhamos como aplicada na V1 (procedimentos.data_aplicacao).
 * Fallbacks, em ordem: aplicado_em da medicação, dt_hr_finalizacao, dt_hr_atendimento.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1) Fonte principal: data de aplicação registrada na V1
            DB::statement("
                UPDATE prescricao_semanas s
                LEFT JOIN procedimentos p ON p.id = s.id_versao1
                SET s.data_aplicada = DATE(p.data_aplicacao)
                WHERE s.situacao IN ('Aplicada', 'Aplicação Parcial')
                  AND s.data_aplicada IS NULL
                  AND p.data_aplicacao IS NOT NULL
            ");

            // 2) Fallback: data efetiva da aplicação na medicação (aplicado_em)
            DB::statement("
                UPDATE prescricao_semanas s
                LEFT JOIN (
                    SELECT prescricao_semana_id, MIN(aplicado_em) AS aplicado_em
                    FROM prescricao_semana_medicamentos
                    WHERE aplicado_em IS NOT NULL
                    GROUP BY prescricao_semana_id
                ) m ON m.prescricao_semana_id = s.id
                SET s.data_aplicada = DATE(m.aplicado_em)
                WHERE s.situacao IN ('Aplicada', 'Aplicação Parcial')
                  AND s.data_aplicada IS NULL
                  AND m.aplicado_em IS NOT NULL
            ");

            // 3) Fallback: dt_hr_finalizacao
            DB::statement("
                UPDATE prescricao_semanas
                SET data_aplicada = DATE(dt_hr_finalizacao)
                WHERE situacao IN ('Aplicada', 'Aplicação Parcial')
                  AND data_aplicada IS NULL
                  AND dt_hr_finalizacao IS NOT NULL
            ");

            // 4) Fallback: dt_hr_atendimento
            DB::statement("
                UPDATE prescricao_semanas
                SET data_aplicada = DATE(dt_hr_atendimento)
                WHERE situacao IN ('Aplicada', 'Aplicação Parcial')
                  AND data_aplicada IS NULL
                  AND dt_hr_atendimento IS NOT NULL
            ");
        });
    }

    public function down(): void
    {
        // Reverte o backfill (somente nas semanas históricas com id_versao1)
        DB::table('prescricao_semanas')
            ->whereNotNull('id_versao1')
            ->whereNotNull('data_aplicada')
            ->update(['data_aplicada' => null]);
    }
};
