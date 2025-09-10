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
        Schema::create('aplicacaos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procedimento_id');
            $table->unsignedBigInteger('medicamento_id');
            $table->unsignedBigInteger('user_id_aplicacao');
            $table->double('quantidade');
            $table->double('valor',10,2);
            $table->double('total',10,2);
            $table->string('situacao');
            $table->text('obs')->nullable();
            $table->foreign('procedimento_id')->references('id')->on('procedimentos');
            $table->foreign('medicamento_id')->references('id')->on('medicamentos');
            $table->foreign('user_id_aplicacao')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicacaos');
    }
};
