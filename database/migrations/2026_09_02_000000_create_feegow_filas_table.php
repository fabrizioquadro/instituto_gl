<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fila de envio para a Feegow (prescrições V2).
 * A aplicação enfileira aqui (sem chamar a Feegow na hora); um robô processa a cada minuto.
 * Se a conexão com a Feegow falhar, a linha fica Pendente e é retentada depois — não trava o sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Observação: a coluna anexos.enviado_feegow já é gerenciada pela migration
        // 2026_08_21_211100_add_enviado_feegow_to_anexos_table — não mexer aqui.
        if (!Schema::hasTable('feegow_filas')) {
            Schema::create('feegow_filas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prescricao_id')->nullable();
                $table->unsignedBigInteger('prescricao_semana_id')->nullable();
                $table->string('evento', 30)->default('aplicacao');      // aplicacao / etc.
                $table->unsignedBigInteger('procedimento_id')->nullable(); // tipo de procedimento na Feegow (ex.: 52 = aplicação)
                $table->json('payload')->nullable();                       // snapshot completo da nota + parâmetros
                $table->string('situacao', 20)->default('Pendente');      // Pendente / Enviado / Erro
                $table->unsignedInteger('tentativas')->default(0);
                $table->timestamp('proxima_tentativa')->nullable();
                $table->timestamp('ultima_tentativa')->nullable();
                $table->text('erro')->nullable();
                $table->timestamp('enviado_em')->nullable();
                $table->timestamps();

                $table->index(['situacao', 'proxima_tentativa']);
                $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
                $table->foreign('prescricao_semana_id')->references('id')->on('prescricao_semanas')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feegow_filas');
    }
};
