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
        Schema::create('financeiros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('paciente_id');
            $table->string('medico')->nullable();
            $table->date('dt_pagamento');
            $table->double('vl_consulta',10,2);
            $table->double('vl_consulta_pagamento',10,2)->nullable();
            $table->double('vl_procedimentos',10,2);
            $table->double('vl_desconto',10,2);
            $table->double('vl_pagamento',10,2);
            $table->string('tipo_pagamento');
            $table->string('forma_pagamento');
            $table->integer('parcelas');
            $table->text('obs_pagamento')->nullable();
            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('paciente_id')->references('id')->on('pacientes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financeiros');
    }
};
