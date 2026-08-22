<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migração de dados: administradors -> users (tipo Administrador)
 *
 * Regras (decisões de negócio de 20/08/2026):
 * - clinica_id = 8 (Estoque Central - clínica padrão do admin)
 * - tipo = 'Administrador'; permissões todas 'Sim'
 * - 4 conflitos de e-mail (mesma pessoa nas duas tabelas):
 *   manter o administradors, excluir o users correspondente,
 *   reapontando todas as referências (FKs) do user antigo para o novo user admin
 * - User fake id=0 é MANTIDO como marcador histórico (290 procedimentos / 94 aplicações não são alterados)
 *
 * Gera tabela de auditoria/rollback `_migracao_adm_map` (fica salva para permitir
 * o down() reverter de forma segura).
 */
return new class extends Migration
{
    /** Clínica padrão atribuída aos administradores (decisão 3.1). */
    private const CLINICA_PADRAO = 8;

    /** Colunas que referenciam users.id (FKs ou colunas de usuário). */
    private const COLUNAS_USER = [
        ['aplicacaos', 'user_id_aplicacao'],
        ['baixas', 'user_id'],
        ['baixa_abertos', 'user_id'],
        ['estoque_abertos', 'user_id'],
        ['financeiro_formas_pagamentos', 'user_id_cadastro'],
        ['procedimentos', 'user_id_aplicacao'],
        ['procedimentos', 'user_id_biopedancia'],
        ['procedimentos', 'user_id_cadastro'],
        ['procedimentos', 'user_id_coleta'],
        ['procedimentos', 'user_id_retirada'],
        ['procedimento_observacaos', 'user_id'],
        ['procedimento_logs', 'usuario_id'],
        ['transferencias', 'user_id'],
    ];

    public function up(): void
    {
        // DDL fora da transação: o MySQL/MariaDB faz COMMIT implícito em DDL
        // (CREATE/DROP TABLE), o que quebraria o rollback dos dados abaixo.
        Schema::dropIfExists('_migracao_adm_map');
        Schema::create('_migracao_adm_map', function (Blueprint $table) {
            $table->unsignedBigInteger('adm_id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('user_conflito_id')->nullable();
            $table->json('user_conflito_json')->nullable();
        });

        DB::transaction(function () {
            $adms = DB::table('administradors')->orderBy('id')->get();

            foreach ($adms as $adm) {
                $user_existente = DB::table('users')->where('email', $adm->email)->first();
                $user_conflito_id = null;
                $user_conflito_json = null;

                if ($user_existente) {
                    $user_conflito_id = $user_existente->id;
                    $user_conflito_json = json_encode((array) $user_existente);
                }

                // Em caso de conflito, insere o admin com e-mail temporário
                // (não pode duplicar o UNIQUE de users.email antes de excluir o user antigo).
                $email_para_insert = $user_existente
                    ? ('migracao_' . $adm->id . '_' . $adm->email)
                    : $adm->email;

                $novo_user_id = DB::table('users')->insertGetId([
                    'clinica_id' => self::CLINICA_PADRAO,
                    'nome' => $adm->nome,
                    'email' => $email_para_insert,
                    'st_usuario' => $adm->st_usuario ?? 'Ativo',
                    'password' => $adm->password,
                    'tipo' => 'Administrador',
                    'coren' => null,
                    'imagem' => $adm->imagem,
                    'imagem_carimbo' => null,
                    'senha_certificado' => null,
                    'dashboard_sec' => 'Sim',
                    'dashboard_enf' => 'Sim',
                    'controle_medicamentos' => 'Sim',
                    'pacientes' => 'Sim',
                    'procedimentos' => 'Sim',
                    'financeiro' => 'Sim',
                    'created_at' => $adm->created_at,
                    'updated_at' => $adm->updated_at,
                ]);

                if ($user_existente) {
                    // Reaponta todas as referências do user antigo para o novo user admin (mesma pessoa)
                    foreach (self::COLUNAS_USER as [$tabela, $coluna]) {
                        DB::table($tabela)->where($coluna, $user_existente->id)->update([$coluna => $novo_user_id]);
                    }
                    // Exclui o user duplicado (o admin prevalece)
                    DB::table('users')->where('id', $user_existente->id)->delete();
                    // Restaura o e-mail real do admin
                    DB::table('users')->where('id', $novo_user_id)->update(['email' => $adm->email]);
                }

                DB::table('_migracao_adm_map')->insert([
                    'adm_id' => $adm->id,
                    'user_id' => $novo_user_id,
                    'user_conflito_id' => $user_conflito_id,
                    'user_conflito_json' => $user_conflito_json,
                ]);
            }

            // Reaponta as referências que apontavam para administradors -> users
            $mapa = DB::table('_migracao_adm_map')->get();
            foreach ($mapa as $m) {
                DB::table('procedimentos')->where('autorizador_sem_pagamento', $m->adm_id)->update(['autorizador_sem_pagamento' => $m->user_id]);
                DB::table('transferencias')->where('administrador_id', $m->adm_id)->update(['administrador_id' => $m->user_id]);
                DB::table('procedimento_logs')->where('administrador_id', $m->adm_id)->update(['administrador_id' => $m->user_id]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $mapa = DB::table('_migracao_adm_map')->get();

            // 1) Reverte FKs que apontavam para administradors
            foreach ($mapa as $m) {
                DB::table('procedimentos')->where('autorizador_sem_pagamento', $m->user_id)->update(['autorizador_sem_pagamento' => $m->adm_id]);
                DB::table('transferencias')->where('administrador_id', $m->user_id)->update(['administrador_id' => $m->adm_id]);
                DB::table('procedimento_logs')->where('administrador_id', $m->user_id)->update(['administrador_id' => $m->adm_id]);
            }

            // 2) Restaura os users de conflito (excluídos no up) e remove os admins inseridos
            foreach ($mapa as $m) {
                if ($m->user_conflito_id) {
                    $dados = json_decode($m->user_conflito_json, true);

                    // Re-insere o user conflitante com e-mail temporário (para não quebrar UNIQUE)
                    DB::table('users')->insert([
                        'id' => $m->user_conflito_id,
                        'clinica_id' => $dados['clinica_id'],
                        'nome' => $dados['nome'],
                        'email' => 'restaurado_' . $m->user_conflito_id . '_' . $dados['email'],
                        'st_usuario' => $dados['st_usuario'],
                        'password' => $dados['password'],
                        'tipo' => $dados['tipo'],
                        'coren' => $dados['coren'] ?? null,
                        'imagem' => $dados['imagem'] ?? null,
                        'imagem_carimbo' => $dados['imagem_carimbo'] ?? null,
                        'senha_certificado' => $dados['senha_certificado'] ?? null,
                        'dashboard_sec' => $dados['dashboard_sec'] ?? null,
                        'dashboard_enf' => $dados['dashboard_enf'] ?? null,
                        'controle_medicamentos' => $dados['controle_medicamentos'] ?? 'Não',
                        'pacientes' => $dados['pacientes'] ?? 'Não',
                        'procedimentos' => $dados['procedimentos'] ?? 'Não',
                        'financeiro' => $dados['financeiro'] ?? 'Não',
                        'created_at' => $dados['created_at'] ?? null,
                        'updated_at' => $dados['updated_at'] ?? null,
                    ]);

                    // Reaponta as referências do admin (user_id) de volta para o user conflitante
                    foreach (self::COLUNAS_USER as [$tabela, $coluna]) {
                        DB::table($tabela)->where($coluna, $m->user_id)->update([$coluna => $m->user_conflito_id]);
                    }

                    // Remove o admin inserido (libera o e-mail original)
                    DB::table('users')->where('id', $m->user_id)->delete();

                    // Restaura o e-mail original do user conflitante
                    DB::table('users')->where('id', $m->user_conflito_id)->update(['email' => $dados['email']]);
                } else {
                    DB::table('users')->where('id', $m->user_id)->delete();
                }
            }
        });

        // DDL fora da transação
        Schema::dropIfExists('_migracao_adm_map');
    }
};
