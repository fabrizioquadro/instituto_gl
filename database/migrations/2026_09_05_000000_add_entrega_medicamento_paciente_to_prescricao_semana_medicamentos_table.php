<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indica que o medicamento da semana foi ENTREGUE ao paciente para aplicação
 * em casa (em vez de ser aplicado na clínica). Padrão: false (aplicado na clínica).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->boolean('entrega_medicamento_paciente')->default(false)->after('gera_aplicacao');
        });
    }

    public function down(): void
    {
        Schema::table('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->dropColumn('entrega_medicamento_paciente');
        });
    }
};
