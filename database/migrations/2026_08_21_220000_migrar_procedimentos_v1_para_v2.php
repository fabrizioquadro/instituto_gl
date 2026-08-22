<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migração de dados V1 -> V2 (Procedimentos + Financeiro).
 *
 * Regras aplicadas (decisões 21/08/2026):
 * - valor_tratamento = SOMA do valor das semanas do grupo - vl_desconto + vl_adicional
 * - parcelas = 1 por semana com aplicação (tem_aplicacao); valor = valor_tratamento/qt_parcelas
 *   (diferença de centavos na última)
 * - pagamentos: 1 evento (prescricao_pagamentos) por financeiro_formas_pagamentos,
 *   com 1 forma; distribuição proporcional na ordem das parcelas (pagamento_parcelas)
 * - NÃO altera tabelas V1 (só lê) — tabelas novas criadas em migrations anteriores
 * - id_versao1/codigo_versao1 preservam o vínculo com a V1
 *
 * Rollback: usa as tabelas de mapa _migracao_v2_* para apagar o que foi criado.
 */
return new class extends Migration
{
    private const MAP_GRUPO = '_migracao_v2_grupo_map';
    private const MAP_SEMANA = '_migracao_v2_semana_map';
    private const MAP_MEDICACAO = '_migracao_v2_medicacao_map';
    private const MAP_FIN = '_migracao_v2_fin_map';

    private function criarMapas(): void
    {
        Schema::dropIfExists(self::MAP_GRUPO);
        Schema::dropIfExists(self::MAP_SEMANA);
        Schema::dropIfExists(self::MAP_MEDICACAO);
        Schema::dropIfExists(self::MAP_FIN);

        Schema::create(self::MAP_GRUPO, function (Blueprint $t) {
            $t->unsignedBigInteger('codigo')->primary();
            $t->unsignedBigInteger('prescricao_id');
        });
        Schema::create(self::MAP_SEMANA, function (Blueprint $t) {
            $t->unsignedBigInteger('procedimento_id')->primary();
            $t->unsignedBigInteger('prescricao_id');
            $t->unsignedBigInteger('prescricao_semana_id');
            $t->boolean('tem_aplicacao')->default(false);
        });
        Schema::create(self::MAP_MEDICACAO, function (Blueprint $t) {
            $t->unsignedBigInteger('aplicacao_id')->primary();
            $t->unsignedBigInteger('prescricao_semana_medicamento_id');
        });
        Schema::create(self::MAP_FIN, function (Blueprint $t) {
            $t->unsignedBigInteger('ffp_id')->primary();
            $t->unsignedBigInteger('prescricao_pagamento_id');
        });
    }

    private function mapearSituacaoSemana($sit): string
    {
        return match ($sit) {
            'Agendado' => 'Agendada',
            'Fila de Aplicação', 'Atendimento', 'Pendente' => 'Em Atendimento',
            'Aplicado' => 'Aplicada',
            'Aplicação Parcial' => 'Aplicação Parcial',
            'Cancelado' => 'Cancelada',
            default => 'Agendada', // Semana Sem Aplicação etc.
        };
    }

    /** Converte datas zero ('0000-00-00') para NULL (sql_mode estrito). */
    private function sane($val)
    {
        if ($val === null || $val === '') {
            return null;
        }
        $s = (string) $val;
        if ($s === '0000-00-00' || $s === '0000-00-00 00:00:00') {
            return null;
        }
        return $val;
    }

    public function up(): void
    {
        ini_set('memory_limit', '1024M');
        $this->criarMapas();

        // ---- Carrega V1 em memória (uma vez) ----
        $procedimentos = DB::table('procedimentos')->orderBy('codigo')->orderBy('nr_procedimento')->get();
        $aplicacaos = DB::table('aplicacaos as a')
            ->leftJoin('medicamentos as m', 'm.id', '=', 'a.medicamento_id')
            ->select('a.*', 'm.aplicacao as med_aplicacao')
            ->orderBy('a.procedimento_id')->get();
        $financeiros = DB::table('financeiros')->get()->keyBy('id');
        $financeiroProc = DB::table('financeiro_procedimentos')->get();
        $ffps = DB::table('financeiro_formas_pagamentos')->get();

        // semana_id -> financeiro_id
        $financeiroPorSemana = [];
        foreach ($financeiroProc as $fp) {
            $financeiroPorSemana[$fp->procedimento_id] = $fp->financeiro_id;
        }
        // financeiro_id -> [ffp...]
        $ffpsPorFinanceiro = [];
        foreach ($ffps as $ffp) {
            $ffpsPorFinanceiro[$ffp->financeiro_id][] = $ffp;
        }
        // indexa aplicações por semana (evita N+1)
        $aplicacaosPorSemana = $aplicacaos->groupBy('procedimento_id');

        DB::transaction(function () use (
            $procedimentos, $aplicacaos, $aplicacaosPorSemana, $financeiros, $financeiroPorSemana, $ffpsPorFinanceiro
        ) {
            $grupos = $procedimentos->groupBy('codigo');
            $semanaBatch = [];
            $medicacaoBatch = [];
            $medicacaoAplicacaoIds = [];
            $grupoMapIns = [];

            foreach ($grupos as $codigo => $semanas) {
                $primeira = $semanas->first();
                $financeiro = null;
                foreach ($semanas as $sem) {
                    if (isset($financeiroPorSemana[$sem->id])) {
                        $financeiro = $financeiros->get($financeiroPorSemana[$sem->id]);
                        break;
                    }
                }

                // ---- mestre ----
                $valorSoma = (float) $semanas->sum('valor');
                $desconto = $financeiro ? (float) $financeiro->vl_desconto : 0;
                $adicional = $financeiro ? (float) $financeiro->vl_adicional : 0;
                $valorTratamento = max(0, round($valorSoma - $desconto + $adicional, 2));

                $qtSemanasAplicacao = 0;
                $totalPago = 0;
                if ($financeiro && isset($ffpsPorFinanceiro[$financeiro->id])) {
                    foreach ($ffpsPorFinanceiro[$financeiro->id] as $ffp) {
                        $totalPago += (float) $ffp->vl_pagamento;
                    }
                }

                // situacao do mestre derivada das semanas
                $situacaoMestre = 'Agendada';
                $tot = $semanas->count();
                $canceladas = 0;
                $aplicadas = 0;
                $andamento = 0;
                foreach ($semanas as $sem) {
                    if ($sem->situacao === 'Cancelado') {
                        $canceladas++;
                    } elseif ($sem->situacao === 'Aplicado') {
                        $aplicadas++;
                    } elseif (in_array($sem->situacao, ['Fila de Aplicação', 'Atendimento', 'Pendente', 'Aplicação Parcial'], true)) {
                        $andamento++;
                    }
                }
                if ($canceladas === $tot) {
                    $situacaoMestre = 'Cancelada';
                } elseif (($aplicadas + $canceladas) === $tot) {
                    $situacaoMestre = 'Concluída';
                } elseif ($andamento > 0) {
                    $situacaoMestre = 'Em Andamento';
                }

                $situacaoFinanceira = 'Em Aberto';
                if ($situacaoMestre === 'Cancelada') {
                    $situacaoFinanceira = 'Cancelado';
                } elseif ($valorTratamento > 0 && $totalPago >= $valorTratamento - 0.005) {
                    $situacaoFinanceira = 'Pago';
                } elseif ($totalPago > 0) {
                    $situacaoFinanceira = 'Parcial';
                }

                $prescricaoId = DB::table('prescricaos')->insertGetId([
                    'codigo_versao1' => (string) $codigo,
                    'paciente_id' => $primeira->paciente_id,
                    'clinica_id' => $primeira->clinica_id,
                    'user_id_cadastro' => $primeira->user_id_cadastro,
                    'medico' => $primeira->medico,
                    'tipo_atendimento' => $primeira->tipo_atendimento,
                    'agendamento' => $primeira->agendamento,
                    'obs' => $primeira->obs,
                    'data_prescricao' => $this->sane($primeira->data_cad ?? $primeira->created_at),
                    'qt_semanas' => $semanas->count(),
                    'qt_semanas_aplicacao' => 0, // preenchido abaixo
                    'qt_parcelas' => 0,
                    'semana_atual' => 0,
                    'valor_tratamento' => $valorTratamento,
                    'credito_em_aberto' => 0,
                    'situacao' => $situacaoMestre,
                    'situacao_financeira' => $situacaoFinanceira,
                    'created_at' => $this->sane($primeira->created_at),
                    'updated_at' => $this->sane($primeira->updated_at),
                ]);
                $grupoMapIns[] = ['codigo' => (int) $codigo, 'prescricao_id' => $prescricaoId];

                // ---- semanas ----
                foreach ($semanas as $sem) {
                    $temAplicacao = false;
                    foreach ($aplicacaosPorSemana->get($sem->id, collect()) as $ap) {
                        if (($ap->med_aplicacao ?? '') === 'Sim') {
                            $temAplicacao = true;
                            break;
                        }
                    }
                    if ($temAplicacao) {
                        $qtSemanasAplicacao++;
                    }
                    $semanaBatch[] = [
                        'id_versao1' => $sem->id,
                        'prescricao_id' => $prescricaoId,
                        'nr_semana' => $sem->nr_procedimento,
                        'data_prevista' => $this->sane($sem->data_aplicacao ?? $sem->data_cad),
                        'tem_aplicacao' => $temAplicacao,
                        'situacao' => $this->mapearSituacaoSemana($sem->situacao),
                        'dt_hr_chegada' => $this->sane($sem->dt_hr_chegada),
                        'dt_hr_atendimento' => $this->sane($sem->dt_hr_atendimento),
                        'dt_hr_finalizacao' => $this->sane($sem->dt_hr_finalizacao),
                        'user_id_aplicacao' => $sem->user_id_aplicacao,
                        'obs' => $sem->obs,
                        'created_at' => $this->sane($sem->created_at),
                        'updated_at' => $this->sane($sem->updated_at),
                    ];
                }

                // atualiza contadores do mestre
                DB::table('prescricaos')->where('id', $prescricaoId)->update([
                    'qt_semanas_aplicacao' => $qtSemanasAplicacao,
                    'qt_parcelas' => $qtSemanasAplicacao,
                ]);
            }

            // ---- batch insert semanas ----
            $semanaMapLocal = [];
            foreach (array_chunk($semanaBatch, 1000) as $chunk) {
                DB::table('prescricao_semanas')->insert($chunk);
            }
            foreach (array_chunk(array_column($semanaBatch, 'id_versao1'), 5000) as $chunkIds) {
                $semanaMapRows = DB::table('prescricao_semanas')->whereIn('id_versao1', $chunkIds)->get();
                foreach ($semanaMapRows as $row) {
                    $semanaMapLocal[$row->id_versao1] = $row;
                }
            }

            // ---- medicações ----
            foreach ($procedimentos as $sem) {
                $semanaInfo = $semanaMapLocal[$sem->id] ?? null;
                if (!$semanaInfo) {
                    continue;
                }
                foreach ($aplicacaosPorSemana->get($sem->id, collect()) as $ap) {
                    $gera = (($ap->med_aplicacao ?? '') === 'Sim');
                    $medicacaoBatch[] = [
                        'id_versao1' => $ap->id,
                        'prescricao_semana_id' => $semanaInfo->id,
                        'medicamento_id' => $ap->medicamento_id,
                        'combo_id' => null,
                        'clinica_id_aplicacao' => $sem->clinica_id_aplicacao,
                        'is_soro' => (bool) $ap->is_soro,
                        'gera_aplicacao' => $gera,
                        'quantidade' => $ap->quantidade,
                        'situacao' => $ap->situacao === 'Aberta' ? 'Aberta' : ($ap->situacao === 'Cancelada' ? 'Cancelada' : 'Aplicada'),
                        'data_prevista' => $this->sane($sem->data_aplicacao ?? $sem->data_cad),
                        'dt_hr_chegada' => $this->sane($ap->dt_hr_chegada),
                        'dt_hr_atendimento' => $this->sane($ap->dt_hr_atendimento),
                        'aplicado_em' => $this->sane($ap->dt_hr_atendimento ?? ($ap->situacao === 'Aplicada' ? $ap->updated_at : null)),
                        'user_id_aplicacao' => $ap->user_id_aplicacao,
                        'obs' => $ap->obs,
                        'created_at' => $this->sane($ap->created_at),
                        'updated_at' => $this->sane($ap->updated_at),
                    ];
                    $medicacaoAplicacaoIds[] = (int) $ap->id;
                }
            }
            $medicacaoMapLocal = [];
            foreach (array_chunk($medicacaoBatch, 1000) as $chunk) {
                DB::table('prescricao_semana_medicamentos')->insert($chunk);
            }
            if ($medicacaoAplicacaoIds) {
                foreach (array_chunk($medicacaoAplicacaoIds, 5000) as $chunkIds) {
                    $medRows = DB::table('prescricao_semana_medicamentos')->whereIn('id_versao1', $chunkIds)->get();
                    foreach ($medRows as $r) {
                        $medicacaoMapLocal[$r->id_versao1] = $r->id;
                    }
                }
            }

            // ---- financeiro (parcelas + pagamentos + formas + distribuição) ----
            $grupoMapById = [];
            foreach ($grupoMapIns as $g) {
                $grupoMapById[(int) $g['codigo']] = $g['prescricao_id'];
            }
            $finMapIns = [];
            foreach ($grupos as $codigo => $semanas) {
                $prescricaoId = $grupoMapById[(int) $codigo] ?? null;
                if (!$prescricaoId) {
                    continue;
                }

                // semanas com aplicação (parcelas)
                $semanasApp = $semanas->filter(function ($sem) use ($semanaMapLocal) {
                    return (int) ($semanaMapLocal[$sem->id]->tem_aplicacao ?? 0) === 1;
                })->values();

                $financeiro = null;
                foreach ($semanas as $sem) {
                    if (isset($financeiroPorSemana[$sem->id])) {
                        $financeiro = $financeiros->get($financeiroPorSemana[$sem->id]);
                        break;
                    }
                }
                $valorTratamento = (float) DB::table('prescricaos')->where('id', $prescricaoId)->value('valor_tratamento');

                // parcelas
                $parcelaIds = [];
                $n = $semanasApp->count();
                if ($n > 0 && $valorTratamento > 0) {
                    $valorParcela = round($valorTratamento / $n, 2);
                    $parcelaCount = 0;
                    foreach ($semanasApp as $sem) {
                        $parcelaCount++;
                        $valor = ($parcelaCount === $n)
                            ? round($valorTratamento - ($valorParcela * ($n - 1)), 2)
                            : $valorParcela;
                        $parcelaId = DB::table('financeiro_parcelas')->insertGetId([
                            'id_versao1' => $sem->id,
                            'prescricao_id' => $prescricaoId,
                            'prescricao_semana_id' => $semanaMapLocal[$sem->id]->id,
                            'nr_parcela' => $parcelaCount,
                            'valor_parcela' => $valor,
                            'valor_pago' => 0,
                            'situacao' => 'Em Aberto',
                            'dt_vencimento' => $sem->data_aplicacao ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $parcelaIds[$parcelaCount] = $parcelaId;
                    }
                }

                // pagamentos (eventos por financeiro_formas_pagamentos)
                $pagamentoEventos = [];
                if ($financeiro && isset($ffpsPorFinanceiro[$financeiro->id])) {
                    foreach ($ffpsPorFinanceiro[$financeiro->id] as $ffp) {
                        $pgId = DB::table('prescricao_pagamentos')->insertGetId([
                            'id_versao1' => $ffp->id,
                            'prescricao_id' => $prescricaoId,
                            'dt_pagamento' => $this->sane($financeiro->dt_pagamento),
                            'vl_total' => (float) $ffp->vl_pagamento,
                            'obs' => null,
                            'user_id' => $ffp->user_id_cadastro,
                            'created_at' => $this->sane($ffp->created_at),
                            'updated_at' => $this->sane($ffp->updated_at),
                        ]);
                        $finMapIns[] = ['ffp_id' => (int) $ffp->id, 'prescricao_pagamento_id' => $pgId];
                        DB::table('prescricao_pagamento_formas')->insert([
                            'id_versao1' => $ffp->id,
                            'pagamento_id' => $pgId,
                            'forma_pagamento' => $ffp->forma_pagamento ?: 'Dinheiro',
                            'vl_pagamento' => (float) $ffp->vl_pagamento,
                            'parcelas' => $ffp->parcelas ?: 1,
                            'id_transacao' => $ffp->id_pagamento,
                            'obs' => null,
                            'created_at' => $this->sane($ffp->created_at),
                            'updated_at' => $this->sane($ffp->updated_at),
                        ]);
                        $pagamentoEventos[] = $pgId;
                    }
                }

                // distribuição proporcional na ordem das parcelas
                if ($n > 0 && $pagamentoEventos && $parcelaIds) {
                    $parcelaIdx = 1;
                    $valoresParcela = [];
                    foreach ($parcelaIds as $nr => $pid) {
                        $valoresParcela[$nr] = [
                            'id' => $pid,
                            'pago' => 0.0,
                            'valor' => (float) DB::table('financeiro_parcelas')->where('id', $pid)->value('valor_parcela'),
                        ];
                    }
                    foreach ($pagamentoEventos as $pgId) {
                        $vlTotal = (float) DB::table('prescricao_pagamentos')->where('id', $pgId)->value('vl_total');
                        $restante = $vlTotal;
                        while ($restante > 0 && $parcelaIdx <= $n) {
                            $p = &$valoresParcela[$parcelaIdx];
                            $falta = $p['valor'] - $p['pago'];
                            if ($falta <= 0) {
                                $parcelaIdx++;
                                continue;
                            }
                            $valor = min($restante, $falta);
                            DB::table('pagamento_parcelas')->insert([
                                'id_versao1' => null,
                                'pagamento_id' => $pgId,
                                'financeiro_parcela_id' => $p['id'],
                                'valor' => $valor,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $p['pago'] += $valor;
                            $restante -= $valor;
                            if ($p['pago'] >= $p['valor'] - 0.005) {
                                $parcelaIdx++;
                            }
                        }
                        unset($p);
                    }
                    foreach ($valoresParcela as $nr => $p) {
                        $sit = 'Em Aberto';
                        if ($p['pago'] >= $p['valor'] - 0.005) {
                            $sit = 'Paga';
                        } elseif ($p['pago'] > 0) {
                            $sit = 'Parcial';
                        }
                        DB::table('financeiro_parcelas')->where('id', $p['id'])->update([
                            'valor_pago' => round($p['pago'], 2),
                            'situacao' => $sit,
                        ]);
                    }
                }
            }

            // ---- grava mapas ----
            foreach (array_chunk($grupoMapIns, 1000) as $chunk) {
                DB::table(self::MAP_GRUPO)->insert($chunk);
            }
            foreach (array_chunk($semanaMapLocal, 1000) as $chunk) {
                $ins = [];
                foreach ($chunk as $row) {
                    $ins[] = [
                        'procedimento_id' => $row->id_versao1,
                        'prescricao_id' => $row->prescricao_id,
                        'prescricao_semana_id' => $row->id,
                        'tem_aplicacao' => (bool) $row->tem_aplicacao,
                    ];
                }
                if ($ins) {
                    DB::table(self::MAP_SEMANA)->insert($ins);
                }
            }
            foreach (array_chunk($medicacaoMapLocal, 1000, true) as $chunk) {
                $ins = [];
                foreach ($chunk as $aplicacaoId => $medId) {
                    $ins[] = ['aplicacao_id' => $aplicacaoId, 'prescricao_semana_medicamento_id' => $medId];
                }
                if ($ins) {
                    DB::table(self::MAP_MEDICACAO)->insert($ins);
                }
            }
            foreach (array_chunk($finMapIns, 1000) as $chunk) {
                DB::table(self::MAP_FIN)->insert($chunk);
            }
        });

        // ---- bulk SQL: anexos, logs, lotes, observações (usam os mapas) ----
        DB::statement("
            INSERT INTO anexos (id_versao1, tipo, prescricao_id, user_id, nm_anexo, arquivo, enviado_feegow, created_at, updated_at)
            SELECT pa.id, 'prescricao', sm.prescricao_id, NULL, pa.nm_anexo, pa.anexo, pa.enviado_feegow,
                COALESCE(NULLIF(pa.created_at, '0000-00-00 00:00:00'), NULL),
                COALESCE(NULLIF(pa.updated_at, '0000-00-00 00:00:00'), NULL)
            FROM procedimento_anexos pa
            JOIN _migracao_v2_semana_map sm ON sm.procedimento_id = pa.procedimento_id
        ");
        DB::statement("
            INSERT INTO prescricao_logs (id_versao1, prescricao_id, entidade, entidade_id, user_id, acao, descricao, dados_antigos, dados_novos, created_at, updated_at)
            SELECT pl.id, sm.prescricao_id, 'semana', sm.prescricao_semana_id, COALESCE(pl.administrador_id, pl.usuario_id), pl.acao, pl.descricao, pl.dados_antigos, pl.dados_novos,
                COALESCE(NULLIF(pl.created_at, '0000-00-00 00:00:00'), NULL),
                COALESCE(NULLIF(pl.updated_at, '0000-00-00 00:00:00'), NULL)
            FROM procedimento_logs pl
            JOIN _migracao_v2_semana_map sm ON sm.procedimento_id = pl.procedimento_id
        ");
        DB::statement("
            INSERT INTO prescricao_lotes (id_versao1, prescricao_semana_medicamento_id, quantidade, lote, codigo_barras, estoque_aberto_id, created_at, updated_at)
            SELECT al.id, mm.prescricao_semana_medicamento_id, al.quantidade, al.lote, al.codigo_barras, al.estoque_aberto_id,
                COALESCE(NULLIF(al.created_at, '0000-00-00 00:00:00'), NULL),
                COALESCE(NULLIF(al.updated_at, '0000-00-00 00:00:00'), NULL)
            FROM aplicacao_lotes al
            JOIN _migracao_v2_medicacao_map mm ON mm.aplicacao_id = al.aplicacao_id
        ");
        DB::statement("
            INSERT INTO prescricao_observacaos (id_versao1, prescricao_semana_id, user_id, observacao, created_at, updated_at)
            SELECT po.id, sm.prescricao_semana_id, po.user_id, po.observacao,
                COALESCE(NULLIF(po.created_at, '0000-00-00 00:00:00'), NULL),
                COALESCE(NULLIF(po.updated_at, '0000-00-00 00:00:00'), NULL)
            FROM procedimento_observacaos po
            JOIN _migracao_v2_semana_map sm ON sm.procedimento_id = po.procedimento_id
        ");
    }

    public function down(): void
    {
        DB::transaction(function () {
            // Apaga apenas o que foi criado (as tabelas novas), na ordem inversa das FKs
            DB::table('pagamento_parcelas')->truncate();
            DB::table('prescricao_pagamento_formas')->truncate();
            DB::table('prescricao_pagamentos')->truncate();
            DB::table('financeiro_parcelas')->truncate();
            DB::table('prescricao_semana_medicamentos')->truncate();
            DB::table('prescricao_semanas')->truncate();
            DB::table('prescricaos')->truncate();
            DB::table('prescricao_lotes')->truncate();
            DB::table('anexos')->truncate();
            DB::table('prescricao_logs')->truncate();
            DB::table('prescricao_observacaos')->truncate();
        });

        Schema::dropIfExists(self::MAP_GRUPO);
        Schema::dropIfExists(self::MAP_SEMANA);
        Schema::dropIfExists(self::MAP_MEDICACAO);
        Schema::dropIfExists(self::MAP_FIN);
    }
};
