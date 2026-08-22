<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Normaliza datas inválidas ('0000-00-00') para NULL.
 *
 * O dump de produção contém datas zero em algumas colunas. Com o sql_mode
 * estrito do Laravel (NO_ZERO_DATE), qualquer rebuild de tabela (ex.: adicionar
 * FK) falha ao revalidar essas linhas. Esta migration corrige os dados e ajusta
 * o schema para permitir NULL onde era NOT NULL.
 *
 * Colunas afetadas (dados levantados em 20/08/2026):
 * - procedimentos.data_aplicacao (36) -> passa a ser NULLABLE
 * - procedimentos.dt_hr_finalizacao (94)
 * - aplicacaos.dt_hr_chegada (131)
 * - aplicacaos.dt_hr_atendimento (131)
 * - pacientes.dt_nascimento (4)
 */
return new class extends Migration
{
    private const DATAS_ZERO = ['0000-00-00', '0000-00-00 00:00:00'];

    private function normalizar($tabela, $coluna): void
    {
        foreach (self::DATAS_ZERO as $zero) {
            DB::table($tabela)->where($coluna, $zero)->update([$coluna => null]);
        }
    }

    public function up(): void
    {
        // 1) Torna data_aplicacao nullable (única coluna NOT NULL com data zero)
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->date('data_aplicacao')->nullable()->change();
        });

        // 2) Corrige os dados
        $this->normalizar('procedimentos', 'data_aplicacao');
        $this->normalizar('procedimentos', 'dt_hr_finalizacao');
        $this->normalizar('aplicacaos', 'dt_hr_chegada');
        $this->normalizar('aplicacaos', 'dt_hr_atendimento');
        $this->normalizar('pacientes', 'dt_nascimento');
    }

    public function down(): void
    {
        // As colunas já eram nullable (exceto data_aplicacao).
        // Para reverter o schema com segurança, preenche os NULLs de
        // data_aplicacao com uma data válida antes de voltar a NOT NULL.
        DB::table('procedimentos')->whereNull('data_aplicacao')->update([
            'data_aplicacao' => '1970-01-01',
        ]);

        Schema::table('procedimentos', function (Blueprint $table) {
            $table->date('data_aplicacao')->nullable(false)->change();
        });

        // Os valores '0000-00-00' originais não são restauráveis em modo estrito;
        // manter como NULL (dados inválidos) nas demais colunas já nullable.
    }
};
