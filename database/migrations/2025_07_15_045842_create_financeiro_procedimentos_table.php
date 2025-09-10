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
        Schema::create('financeiro_procedimentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financeiro_id');
            $table->unsignedBigInteger('procedimento_id');
            $table->foreign('financeiro_id')->references('id')->on('financeiros');
            $table->foreign('procedimento_id')->references('id')->on('procedimentos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financeiro_procedimentos');
    }
};
