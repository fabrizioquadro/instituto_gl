<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Configuracao;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracaos', function (Blueprint $table) {
            $table->integer('id');
            $table->date('ultima_atualizacao_pacientes');
            $table->timestamps();
        });

        $dados = [
            'id' => '1',
            'ultima_atualizacao_pacientes' => '2025-7-14',
        ];
        Configuracao::create($dados);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracaos');
    }
};
