<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda o estado completo das parcelas ANTES de um pagamento reestruturado (modo 1),
 * para que a exclusão do pagamento possa restaurar os valores originais das parcelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricao_pagamentos', function (Blueprint $table) {
            $table->json('snapshot_parcelas')->nullable()->after('obs');
        });
    }

    public function down(): void
    {
        Schema::table('prescricao_pagamentos', function (Blueprint $table) {
            $table->dropColumn('snapshot_parcelas');
        });
    }
};
