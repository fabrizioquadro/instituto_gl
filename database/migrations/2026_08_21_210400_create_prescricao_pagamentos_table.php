<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evento de pagamento (guarda o total; as formas ficam em prescricao_pagamento_formas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('prescricao_id');
            $table->date('dt_pagamento');
            $table->decimal('vl_total', 10, 2);
            $table->text('obs')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_pagamentos');
    }
};
