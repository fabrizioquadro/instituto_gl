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
        Schema::table('aplicacaos', function (Blueprint $table) {
            $table->dateTime('dt_hr_chegada')->nullable();
            $table->dateTime('dt_hr_atendimento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aplicacaos', function (Blueprint $table) {
            $table->dropColumn(['dt_hr_chegada', 'dt_hr_atendimento']);
        });
    }
};
