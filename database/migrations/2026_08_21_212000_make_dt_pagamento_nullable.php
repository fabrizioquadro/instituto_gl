<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * prescricao_pagamentos.dt_pagamento passa a ser nullable
 * (na V1, 362 financeiros não têm dt_pagamento).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricao_pagamentos', function (Blueprint $table) {
            $table->date('dt_pagamento')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('prescricao_pagamentos', function (Blueprint $table) {
            $table->date('dt_pagamento')->nullable(false)->change();
        });
    }
};
