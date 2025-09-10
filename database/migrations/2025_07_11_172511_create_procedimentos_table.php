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
        Schema::create('procedimentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('codigo');
            $table->integer('nr_procedimento');
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('clinica_id_aplicacao');
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('user_id_aplicacao');
            $table->date('data_cad');
            $table->date('data_aplicacao');
            $table->date('data_pagamento')->nullable();
            $table->double('valor');
            $table->string('st_pagamento');
            $table->string('situacao');
            $table->string('medico');
            $table->text('obs')->nullable();
            $table->string('tipo_pagamento')->nullable();
            $table->string('forma_pagamento')->nullable();
            $table->integer('parcelas')->nullable();
            $table->double('vl_pago',10,2)->nullable();
            $table->text('obs_pagamento')->nullable();
            $table->string('st_biopedancia',5)->nullable();
            $table->text('obs_biopedancia')->nullable();
            $table->string('st_coleta',5)->nullable();
            $table->string('tp_coleta')->nullable();
            $table->text('obs_coleta')->nullable();
            $table->string('semana_sem_aplicacao')->nullable();
            $table->unsignedBigInteger('autorizador_sem_pagamento')->nullable();
            $table->string('consulta_tratamento_agendada',10)->nullable();
            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('clinica_id_aplicacao')->references('id')->on('clinicas');
            $table->foreign('paciente_id')->references('id')->on('pacientes');
            $table->foreign('user_id_aplicacao')->references('id')->on('users');
            $table->foreign('autorizador_sem_pagamento')->references('id')->on('administradors');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedimentos');
    }
};
