<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona a data em que a semana foi aplicada (preenchida daqui para a frente;
 * semanas antigas permanecem null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricao_semanas', function (Blueprint $table) {
            $table->date('data_aplicada')->nullable()->after('data_prevista');
        });
    }

    public function down(): void
    {
        Schema::table('prescricao_semanas', function (Blueprint $table) {
            $table->dropColumn('data_aplicada');
        });
    }
};
