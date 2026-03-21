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
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('fabricante');
            $table->string('unidade');
            $table->integer('vasilhame')->nullable();
            $table->double('ultimo_valor_pg',10,2)->nullable();
            $table->string('vl_venda',10,2);
            $table->double('estoque_minimo')->default('0');
            $table->string('situacao');
            $table->string('aplicacao',5);
            $table->integer('aplicacao_feegow_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};
