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
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->dateTime('inicio_cadastro')->nullable()->after('user_id_cadastro');
            $table->dateTime('finalizacao_cadastro')->nullable()->after('inicio_cadastro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->dropColumn(['inicio_cadastro', 'finalizacao_cadastro']);
        });
    }
};
