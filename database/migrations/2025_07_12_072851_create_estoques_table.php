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
        Schema::create('estoques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('entrada_id')->nullable();
            $table->unsignedBigInteger('baixa_id')->nullable();
            $table->unsignedBigInteger('transferencia_id')->nullable();
            $table->unsignedBigInteger('procedimento_id')->nullable();
            $table->unsignedBigInteger('medicamento_id')->nullable();
            $table->string('origem');
            $table->string('tipo');
            $table->double('quantidade');
            $table->double('valor',10,2);
            $table->double('total',10,2);
            $table->string('lote');
            $table->date('dt_vencimento');
            $table->string('codigo_barras')->nullable();
            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('entrada_id')->references('id')->on('entradas');
            $table->foreign('baixa_id')->references('id')->on('baixas');
            $table->foreign('transferencia_id')->references('id')->on('transferencias');
            $table->foreign('procedimento_id')->references('id')->on('procedimentos');
            $table->foreign('medicamento_id')->references('id')->on('medicamentos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoques');
    }
};
