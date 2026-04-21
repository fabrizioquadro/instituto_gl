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
        Schema::create('procedimento_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procedimento_id');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('administrador_id')->nullable();
            $table->string('acao');
            $table->text('descricao')->nullable();
            $table->json('dados_antigos')->nullable();
            $table->json('dados_novos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedimento_logs');
    }
};
