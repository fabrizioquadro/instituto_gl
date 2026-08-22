<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration de schema: recria a FK de procedimentos.autorizador_sem_pagamento -> users
 * e remove a tabela administradors.
 *
 * A FK antiga (-> administradors) já foi removida pela migration
 * 2026_08_20_175000_drop_fk_autorizador_administradors, e os dados foram migrados
 * pela 2026_08_20_180000_migrar_administradors_para_users.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Aponta a FK de autorizador_sem_pagamento para users
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->foreign('autorizador_sem_pagamento')->references('id')->on('users');
        });

        // 2) Remove a tabela administradors (agora vazia)
        Schema::dropIfExists('administradors');
    }

    public function down(): void
    {
        // 1) Recria a tabela administradors com a estrutura original (+ st_usuario)
        Schema::create('administradors', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email');
            $table->string('st_usuario')->default('Ativo')->after('email');
            $table->string('password');
            $table->string('imagem')->nullable();
            $table->timestamps();
        });

        // 2) Remove a FK que aponta para users (a FK para administradors é
        //    recriada pelo down() da migration 175000, que roda depois desta)
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->dropForeign(['autorizador_sem_pagamento']);
        });
    }
};
