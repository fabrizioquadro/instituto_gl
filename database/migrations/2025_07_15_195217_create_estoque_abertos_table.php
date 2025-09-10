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
        Schema::create('estoque_abertos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medicamento_id');
            $table->unsignedBigInteger('procedimento_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('clinica_id');
            $table->string('identificador');
            $table->date('dt_cadastro');
            $table->integer('qt_inical');
            $table->integer('qt_utilizado');
            $table->integer('qt_restante');
            $table->string('lote');
            $table->string('codigo_barras')->nullable();
            $table->string('situacao');
            $table->foreign('medicamento_id')->references('id')->on('medicamentos');
            $table->foreign('procedimento_id')->references('id')->on('procedimentos');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoque_abertos');
    }
};
