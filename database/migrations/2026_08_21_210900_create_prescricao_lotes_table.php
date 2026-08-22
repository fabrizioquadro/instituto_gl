<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nova tabela V2 que substitui a função de aplicacao_lotes (sem alterar a original):
 * lote/código de barras usado na aplicação de cada medicação da semana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_lotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('prescricao_semana_medicamento_id');
            $table->double('quantidade');
            $table->string('lote')->nullable();
            $table->string('codigo_barras')->nullable();
            $table->unsignedBigInteger('estoque_aberto_id')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_semana_medicamento_id')->references('id')->on('prescricao_semana_medicamentos');
            $table->foreign('estoque_aberto_id')->references('id')->on('estoque_abertos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_lotes');
    }
};
