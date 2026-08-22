<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anexos unificados: prescrição médica e comprovantes/demonstrativos de pagamento,
 * com rastreio de visualização (visualizado_em/por) para a regra R3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->string('tipo'); // prescricao | comprovante_pagamento | demonstrativo_pagamento
            $table->unsignedBigInteger('prescricao_id')->nullable();
            $table->unsignedBigInteger('pagamento_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nm_anexo');
            $table->string('arquivo');
            $table->string('mime')->nullable();
            $table->string('extensao')->nullable();
            $table->dateTime('visualizado_em')->nullable();
            $table->unsignedBigInteger('visualizado_por')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('pagamento_id')->references('id')->on('prescricao_pagamentos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('visualizado_por')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
