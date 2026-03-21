<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financeiro_formas_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financeiro_id');
            $table->string('forma_pagamento');
            $table->integer('parcelas');
            $table->double('vl_pagamento');
            $table->unsignedBigInteger('user_id_cadastro');
            $table->foreign('financeiro_id')->references('id')->on('financeiros');
            $table->foreign('user_id_cadastro')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financeiro_formas_pagamentos');
    }
};
