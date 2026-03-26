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
        Schema::table('procedimentos', function (Blueprint $row) {
            $row->boolean('flag_coordenacao')->default(false);
            $row->boolean('flag_qualidade')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedimentos', function (Blueprint $row) {
            $row->dropColumn('flag_coordenacao');
            $row->dropColumn('flag_qualidade');
        });
    }
};
