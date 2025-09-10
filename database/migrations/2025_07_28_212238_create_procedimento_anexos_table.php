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
        Schema::create('procedimento_anexos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procedimento_id');
            $table->string('nm_anexo');
            $table->string('anexo')->nullable();
            $table->foreign('procedimento_id')->references('id')->on('procedimentos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedimento_anexos');
    }
};
