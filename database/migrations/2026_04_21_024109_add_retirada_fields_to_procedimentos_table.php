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
            $table->string('st_retirada')->nullable()->default('Não')->after('obs_coleta');
            $table->text('obs_retirada')->nullable()->after('st_retirada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->dropColumn(['st_retirada', 'obs_retirada']);
        });
    }
};
