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
        Schema::table('users', function (Blueprint $table) {
            $table->string('st_usuario')->default('Ativo')->after('email');
        });

        Schema::table('administradors', function (Blueprint $table) {
            $table->string('st_usuario')->default('Ativo')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('st_usuario');
        });

        Schema::table('administradors', function (Blueprint $table) {
            $table->dropColumn('st_usuario');
        });
    }
};
