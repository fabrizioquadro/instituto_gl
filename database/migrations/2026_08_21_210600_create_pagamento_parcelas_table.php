<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O QUE o pagamento pagou: qual parcela cada valor cobre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamento_parcelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('pagamento_id');
            $table->unsignedBigInteger('financeiro_parcela_id');
            $table->decimal('valor', 10, 2);
            $table->timestamps();

            $table->foreign('pagamento_id')->references('id')->on('prescricao_pagamentos')->onDelete('cascade');
            $table->foreign('financeiro_parcela_id')->references('id')->on('financeiro_parcelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_parcelas');
    }
};
