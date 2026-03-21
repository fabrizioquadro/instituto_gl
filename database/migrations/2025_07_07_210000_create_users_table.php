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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinica_id');
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('tipo');
            $table->string('coren')->nullable();
            $table->string('imagem')->nullable();
            $table->string('imagem_carimbo')->nullable();
            $table->string('senha_certificado')->nullable();
            $table->string('dashboard_sec',5)->nullable();
            $table->string('dashboard_enf',5)->nullable();
            $table->string('controle_medicamentos',5);
            $table->string('pacientes',5);
            $table->string('procedimentos',5);
            $table->string('financeiro',5);
            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
