<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Observações por semana da prescrição (nova tabela V2, espelho de procedimento_observacaos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_observacaos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('prescricao_semana_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('observacao');
            $table->timestamps();

            $table->foreign('prescricao_semana_id')->references('id')->on('prescricao_semanas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_observacaos');
    }
};
