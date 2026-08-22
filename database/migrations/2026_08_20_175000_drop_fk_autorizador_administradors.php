<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove a FK de procedimentos.autorizador_sem_pagamento -> administradors.
 *
 * Deve rodar ANTES da migration de dados (2026_08_20_180000_migrar_administradors_para_users)
 * para permitir reapontar os ids de administradors para users sem violar a constraint.
 * A FK para users é recriada na migration 2026_08_20_181000_drop_administradors_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->dropForeign(['autorizador_sem_pagamento']);
        });
    }

    public function down(): void
    {
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->foreign('autorizador_sem_pagamento')->references('id')->on('administradors');
        });
    }
};
