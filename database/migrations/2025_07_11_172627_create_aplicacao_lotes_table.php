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
        Schema::create('aplicacao_lotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aplicacao_id');
            $table->double('quantidade');
            $table->string('lote');
            $table->string('codigo_barras')->nullable();
            $table->foreign('aplicacao_id')->references('id')->on('aplicacaos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicacao_lotes');
    }
};
