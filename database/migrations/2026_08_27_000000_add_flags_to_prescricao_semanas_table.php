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
        Schema::table('prescricao_semanas', function (Blueprint $row) {
            $row->boolean('flag_coordenacao')->default(false);
            $row->boolean('flag_qualidade')->default(false);
            $row->string('user_nome_coordenacao')->nullable();
            $row->string('user_nome_qualidade')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescricao_semanas', function (Blueprint $row) {
            $row->dropColumn(['flag_coordenacao', 'flag_qualidade', 'user_nome_coordenacao', 'user_nome_qualidade']);
        });
    }
};
