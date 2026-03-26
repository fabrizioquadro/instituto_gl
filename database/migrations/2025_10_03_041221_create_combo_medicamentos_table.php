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
        Schema::create('combo_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('combo_id');
            $table->unsignedBigInteger('medicamento_id');
            $table->double('quantidade');
            $table->double('valor_unitario',10,2);
            $table->foreign('combo_id')->references('id')->on('combos');
            $table->foreign('medicamento_id')->references('id')->on('medicamentos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_medicamentos');
    }
};
