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
            $table->text('agendamento')->nullable()->after('medico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedimentos', function (Blueprint $table) {
            $table->dropColumn('agendamento');
        });

    }
};
