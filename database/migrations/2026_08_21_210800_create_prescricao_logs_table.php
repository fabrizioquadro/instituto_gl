<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria da prescrição (imutável): linha do tempo de todas as mudanças.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('prescricao_id')->index();
            $table->string('entidade'); // prescricao | semana | medicamento | parcela | pagamento | reajuste | anexo
            $table->unsignedBigInteger('entidade_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('acao');
            $table->text('descricao')->nullable();
            $table->json('dados_antigos')->nullable();
            $table->json('dados_novos')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_logs');
    }
};
