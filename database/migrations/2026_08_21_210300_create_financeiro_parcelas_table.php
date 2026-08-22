<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parcelas financeiras (1 por semana com aplicação) — previsão a receber.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financeiro_parcelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('prescricao_id');
            $table->unsignedBigInteger('prescricao_semana_id');
            $table->integer('nr_parcela');
            $table->decimal('valor_parcela', 10, 2);
            $table->decimal('valor_pago', 10, 2)->default(0);
            $table->string('situacao');
            $table->date('dt_vencimento')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('prescricao_semana_id')->references('id')->on('prescricao_semanas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financeiro_parcelas');
    }
};
