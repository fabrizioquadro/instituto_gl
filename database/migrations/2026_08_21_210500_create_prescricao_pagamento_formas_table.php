<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COMO o pagamento foi pago (forma, valor, parcelas do cartão, TID/autorização).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_pagamento_formas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('pagamento_id');
            $table->string('forma_pagamento');
            $table->decimal('vl_pagamento', 10, 2);
            $table->integer('parcelas')->default(1);
            $table->string('id_transacao')->nullable();
            $table->text('obs')->nullable();
            $table->timestamps();

            $table->foreign('pagamento_id')->references('id')->on('prescricao_pagamentos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_pagamento_formas');
    }
};
