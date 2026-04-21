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
        Schema::table('financeiro_formas_pagamentos', function (Blueprint $table) {
            $table->string('id_pagamento')->nullable()->after('vl_pagamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financeiro_formas_pagamentos', function (Blueprint $table) {
            $table->dropColumn('id_pagamento');
        });
    }
};
