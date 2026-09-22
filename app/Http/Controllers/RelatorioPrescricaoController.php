<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Estoque;
use App\Models\Medicamento;
use App\Models\Prescricao;
use App\Models\PrescricaoPagamento;
use App\Models\PrescricaoPagamentoForma;
use App\Models\PrescricaoSemanaMedicamento;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * RELATÓRIOS 2 — focados nas PRESCRIÇÕES (V2) e seus pagamentos.
 *
 * Espelha os relatórios da V1 (RelatorioController), mas lendo as tabelas do
 * módulo de prescrições: prescricaos, prescricao_semanas,
 * prescricao_semana_medicamentos, financeiro_parcelas, prescricao_pagamentos,
 * prescricao_pagamento_formas, pagamento_parcelas e prescricao_lotes.
 *
 * Transferências, Baixas e Estoque continuam apontando para as rotas da V1
 * (não existe equivalente em prescrição) — ver o menu lateral.
 */
class RelatorioPrescricaoController extends Controller
{
    /* =====================================================================
     |  HELPERS
     ===================================================================== */

    private function filtros(Request $request)
    {
        return $request->except('_token');
    }

    /**
     * Filtros comuns das prescrições.
     * $filtrar_periodo = false quando as datas do form valem para PAGAMENTO
     * (financeiro / financeiro simplificado / caixa) e não para o cadastro.
     */
    private function query_prescricoes(array $dados, $filtrar_periodo = true)
    {
        $query = Prescricao::query();

        if (!empty($dados['paciente_id'])) {
            $query->where('paciente_id', $dados['paciente_id']);
        }
        if (!empty($dados['clinica_id'])) {
            $query->where('clinica_id', $dados['clinica_id']);
        }
        if (!empty($dados['medico'])) {
            $query->where('medico', $dados['medico']);
        }
        if (!empty($dados['situacao'])) {
            $query->where('situacao', $dados['situacao']);
        }
        if (!empty($dados['situacao_financeira'])) {
            $query->where('situacao_financeira', $dados['situacao_financeira']);
        }
        if (!empty($dados['user_id_cadastro'])) {
            $query->where('user_id_cadastro', $dados['user_id_cadastro']);
        }

        if ($filtrar_periodo) {
            if (!empty($dados['dt_inc'])) {
                $query->where('data_prescricao', '>=', $dados['dt_inc']);
            }
            if (!empty($dados['dt_fn'])) {
                $query->where('data_prescricao', '<=', $dados['dt_fn']);
            }
        }

        return $query;
    }

    private function colaboradores()
    {
        return User::where('st_usuario', 'Ativo')->orderBy('nome')->get();
    }

    private function enfermeiras()
    {
        return User::where('tipo', 'Enfermagem')->where('st_usuario', 'Ativo')->orderBy('nome')->get();
    }

    /** Código de exibição da prescrição: usa o código migrado da V1 quando existir. */
    private function codigo_prescricao($prescricao)
    {
        if (!$prescricao) {
            return '';
        }

        return $prescricao->codigo_versao1 ?: $prescricao->id;
    }

    private function data_hora($valor)
    {
        if (!$valor) {
            return '';
        }
        $partes = explode(' ', $valor);

        return dataDbForm($partes[0]) . ' ' . ($partes[1] ?? '');
    }

    private function data_simples($valor)
    {
        if (!$valor) {
            return '';
        }
        $partes = explode(' ', $valor);

        return dataDbForm($partes[0]);
    }

    private function reais($valor)
    {
        return 'R$ ' . valorDbForm($valor ?? 0);
    }

    /** Cache de vencimento por lote (evita repetir a consulta de estoque). */
    private $cache_vencimento = [];

    /** Cache de nome de clínica (id => nome). */
    private $cache_clinicas = null;

    private function clinica_nome($clinica_id)
    {
        if (!$clinica_id) {
            return null;
        }
        if ($this->cache_clinicas === null) {
            $this->cache_clinicas = Clinica::pluck('nome', 'id')->all();
        }

        return $this->cache_clinicas[$clinica_id] ?? null;
    }

    private function vencimento_lote($lote, $medicamento_id)
    {
        if ($lote->estoque_aberto_id) {
            return '-';
        }

        $chave = $medicamento_id . '|' . $lote->lote . '|' . $lote->codigo_barras;
        if (array_key_exists($chave, $this->cache_vencimento)) {
            return $this->cache_vencimento[$chave];
        }

        $estoque = Estoque::where('medicamento_id', $medicamento_id)
            ->where('lote', $lote->lote)
            ->where('codigo_barras', $lote->codigo_barras)
            ->orderByDesc('id')
            ->first();

        $retorno = ($estoque && $estoque->dt_vencimento) ? dataDbForm($estoque->dt_vencimento) : '-';

        return $this->cache_vencimento[$chave] = $retorno;
    }

    private function lotes_html($item)
    {
        $lotes = $item->lotes;
        if ($lotes->count() <= 1) {
            foreach ($lotes as $lote) {
                return $this->lote_com_quantidade($lote);
            }

            return '';
        }

        $retorno = [];
        foreach ($lotes as $lote) {
            $retorno[] = $this->lote_com_quantidade($lote);
        }

        return implode('<br>', $retorno);
    }

    private function lote_com_quantidade($lote)
    {
        return 'Lote: ' . $lote->lote . ', Qtd: ' . valorDbForm($lote->quantidade);
    }

    private function codigos_html($item)
    {
        $lotes = $item->lotes;
        if ($lotes->count() <= 1) {
            foreach ($lotes as $lote) {
                return (string) $lote->codigo_barras;
            }

            return '';
        }

        $retorno = [];
        foreach ($lotes as $lote) {
            $retorno[] = (string) $lote->codigo_barras;
        }

        return implode('<br>', $retorno);
    }

    private function vencimentos_html($item, $medicamento_id)
    {
        $lotes = $item->lotes;
        $retorno = [];
        foreach ($lotes as $lote) {
            $retorno[] = $this->vencimento_lote($lote, $medicamento_id);
        }

        return implode('<br>', $retorno);
    }

    private function exportar_xlsx($nome_arquivo, array $cabecalho, array $linhas)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $linha_atual = 1;
        $sheet->fromArray($cabecalho, null, 'A' . $linha_atual);

        foreach ($linhas as $linha) {
            $linha_atual++;
            $sheet->fromArray(array_values($linha), null, 'A' . $linha_atual);
        }

        $dir = public_path('rel_prescricoes');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $nome_arquivo . '_' . date('YmdHis') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }

    /* =====================================================================
     |  FINANCEIRO  (pagamentos das prescrições, com rateio por semana)
     ===================================================================== */

    public function financeiro()
    {
        $clinicas = Clinica::all()->sortBy('nome');
        $medicos = api()->get_medicos();
        $usuarios = $this->colaboradores();

        return view('adm/relatorios2/financeiro', compact('clinicas', 'medicos', 'usuarios'));
    }

    public function financeiro_gerar(Request $request)
    {
        $dados = $this->filtros($request);
        $array_financeiro = $this->montar_financeiro($dados);

        return view('adm/relatorios2/financeiro_gerar', compact('array_financeiro', 'dados'));
    }

    private function montar_financeiro(array $dados)
    {
        $prescricoes = $this->query_prescricoes($dados, false)
            ->with(['paciente', 'clinica'])
            ->get();

        if ($prescricoes->isEmpty()) {
            return [];
        }

        $pagamentos = PrescricaoPagamento::query()
            ->with(['formas', 'parcelas.financeiroParcela.semana', 'user'])
            ->whereIn('prescricao_id', $prescricoes->pluck('id')->all())
            ->when(!empty($dados['dt_inc']), function ($q) use ($dados) {
                $q->where('dt_pagamento', '>=', $dados['dt_inc']);
            })
            ->when(!empty($dados['dt_fn']), function ($q) use ($dados) {
                $q->where('dt_pagamento', '<=', $dados['dt_fn']);
            })
            ->when(!empty($dados['user_id']), function ($q) use ($dados) {
                $q->where('user_id', $dados['user_id']);
            })
            ->orderBy('dt_pagamento')
            ->orderBy('id')
            ->get();

        $linhas = [];

        foreach ($pagamentos as $pagamento) {
            $prescricao = $prescricoes->firstWhere('id', $pagamento->prescricao_id);
            if (!$prescricao) {
                continue;
            }

            $vl_total = (float) $pagamento->vl_total;

            foreach ($pagamento->formas as $forma) {
                $fator = $vl_total > 0 ? ((float) $forma->vl_pagamento / $vl_total) : 0;

                $comum = [
                    'ordem' => strtotime($pagamento->dt_pagamento ?? $pagamento->created_at),
                    'data' => $this->data_simples($pagamento->dt_pagamento ?? $pagamento->created_at),
                    'prescricao_id' => $prescricao->id,
                    'pagamento_id' => $pagamento->id,
                    'paciente' => $prescricao->paciente->nm_paciente ?? 'N/A',
                    'paciente_id' => $prescricao->paciente_id,
                    'id_feegow' => $prescricao->paciente->paciente_id_feegow ?? '',
                    'cpf' => $prescricao->paciente->cpf ?? '',
                    'codigo' => $this->codigo_prescricao($prescricao),
                    'vl_tratamento' => $this->reais($prescricao->valor_tratamento),
                    'credito_total' => $this->reais($prescricao->credito_em_aberto),
                    'vl_pagamento' => $this->reais($forma->vl_pagamento),
                    'forma_pagamento' => $forma->forma_pagamento,
                    'id_pagamento' => $forma->id_transacao ?: $pagamento->id,
                    'parcelas' => $forma->parcelas,
                    'tipo_atendimento' => $prescricao->tipo_atendimento,
                    'clinica' => $prescricao->clinica->nome ?? 'N/A',
                    'medico' => $prescricao->medico,
                    'colaborador' => $pagamento->user->nome ?? 'Não identificado',
                    'situacao_financeira' => $prescricao->situacao_financeira,
                    'obs' => $pagamento->obs,
                ];

                // pagamento sem rateio registrado (ex.: dado migrado): linha única do protocolo
                if ($pagamento->parcelas->count() === 0) {
                    $linhas[] = $comum + [
                        'tp_pagamento' => 'Protocolo',
                        'vl_rateio' => $this->reais($forma->vl_pagamento),
                    ];
                    continue;
                }

                foreach ($pagamento->parcelas as $pp) {
                    $rateio = round((float) $pp->valor * $fator, 2);
                    if ($rateio <= 0) {
                        continue;
                    }

                    $parcela = $pp->financeiroParcela;
                    $semana = $parcela->semana ?? null;

                    $linhas[] = $comum + [
                        'tp_pagamento' => $semana ? 'Semana ' . $semana->nr_semana : 'Protocolo',
                        'vl_rateio' => $this->reais($rateio),
                    ];
                }
            }
        }

        usort($linhas, function ($a, $b) {
            return $a['ordem'] <=> $b['ordem'];
        });

        return $linhas;
    }

    public function exportar_financeiro(Request $request)
    {
        $dados = json_decode($request->dados, true) ?? [];
        $linhas = $this->montar_financeiro($dados);

        $cabecalho = [
            'ID', 'Data', 'Paciente', 'ID Feegow', 'CPF', 'Código', 'Valor Tratamento',
            'Crédito em Aberto', 'Pagamento', 'Valor Rateio', 'Tipo', 'Atendimento',
            'Forma Pagamento', 'ID Pagamento', 'Parcelas', 'Clínica', 'Médico',
            'Colaborador', 'Situação Financeira', 'Obs',
        ];

        $saida = [];
        foreach ($linhas as $l) {
            $saida[] = [
                $l['pagamento_id'], $l['data'], $l['paciente'], $l['id_feegow'], $l['cpf'],
                $l['codigo'], $l['vl_tratamento'], $l['credito_total'], $l['vl_pagamento'],
                $l['vl_rateio'], $l['tp_pagamento'], $l['tipo_atendimento'],
                $l['forma_pagamento'], $l['id_pagamento'], $l['parcelas'], $l['clinica'],
                $l['medico'], $l['colaborador'], $l['situacao_financeira'], $l['obs'],
            ];
        }

        return $this->exportar_xlsx('Financeiro_Prescricoes', $cabecalho, $saida);
    }

    /* =====================================================================
     |  FINANCEIRO SIMPLIFICADO  (1 linha por forma: aplicações x procedimentos)
     ===================================================================== */

    public function financeiro_simplificado()
    {
        $clinicas = Clinica::all()->sortBy('nome');
        $medicos = api()->get_medicos();
        $usuarios = $this->colaboradores();

        return view('adm/relatorios2/financeiro_simplificado', compact('clinicas', 'medicos', 'usuarios'));
    }

    public function financeiro_simplificado_gerar(Request $request)
    {
        $dados = $this->filtros($request);
        $array_financeiro = $this->montar_financeiro_simplificado($dados);

        return view('adm/relatorios2/financeiro_simplificado_gerar', compact('array_financeiro', 'dados'));
    }

    private function montar_financeiro_simplificado(array $dados)
    {
        $prescricoes = $this->query_prescricoes($dados, false)
            ->with(['paciente', 'clinica'])
            ->get();

        if ($prescricoes->isEmpty()) {
            return [];
        }

        $pagamentos = PrescricaoPagamento::query()
            ->with(['formas', 'parcelas.financeiroParcela.semana', 'user'])
            ->whereIn('prescricao_id', $prescricoes->pluck('id')->all())
            ->when(!empty($dados['dt_inc']), function ($q) use ($dados) {
                $q->where('dt_pagamento', '>=', $dados['dt_inc']);
            })
            ->when(!empty($dados['dt_fn']), function ($q) use ($dados) {
                $q->where('dt_pagamento', '<=', $dados['dt_fn']);
            })
            ->when(!empty($dados['user_id']), function ($q) use ($dados) {
                $q->where('user_id', $dados['user_id']);
            })
            ->orderBy('dt_pagamento')
            ->orderBy('id')
            ->get();

        $linhas = [];

        foreach ($pagamentos as $pagamento) {
            $prescricao = $prescricoes->firstWhere('id', $pagamento->prescricao_id);
            if (!$prescricao) {
                continue;
            }

            $vl_total = (float) $pagamento->vl_total;

            foreach ($pagamento->formas as $forma) {
                $fator = $vl_total > 0 ? ((float) $forma->vl_pagamento / $vl_total) : 0;

                $vl_aplicacoes = 0;
                $vl_procedimentos = 0;

                foreach ($pagamento->parcelas as $pp) {
                    $rateio = (float) $pp->valor * $fator;
                    $semana = $pp->financeiroParcela->semana ?? null;
                    if ($semana && $semana->tem_aplicacao) {
                        $vl_aplicacoes += $rateio;
                    } else {
                        $vl_procedimentos += $rateio;
                    }
                }

                // pagamento sem rateio (dado migrado) -> joga tudo em procedimentos
                if ($pagamento->parcelas->count() === 0) {
                    $vl_procedimentos = (float) $forma->vl_pagamento;
                }

                $linhas[] = [
                    'ordem' => strtotime($pagamento->dt_pagamento ?? $pagamento->created_at),
                    'data' => $this->data_simples($pagamento->dt_pagamento ?? $pagamento->created_at),
                    'prescricao_id' => $prescricao->id,
                    'pagamento_id' => $pagamento->id,
                    'paciente' => $prescricao->paciente->nm_paciente ?? 'N/A',
                    'paciente_id' => $prescricao->paciente_id,
                    'id_feegow' => $prescricao->paciente->paciente_id_feegow ?? '',
                    'cpf' => $prescricao->paciente->cpf ?? '',
                    'codigo' => $this->codigo_prescricao($prescricao),
                    'vl_tratamento' => $this->reais($prescricao->valor_tratamento),
                    'credito_total' => $this->reais($prescricao->credito_em_aberto),
                    'vl_pagamento' => $this->reais($forma->vl_pagamento),
                    'vl_procedimentos' => $this->reais($vl_procedimentos),
                    'vl_aplicacoes' => $this->reais($vl_aplicacoes),
                    'tipo_atendimento' => $prescricao->tipo_atendimento,
                    'forma_pagamento' => $forma->forma_pagamento,
                    'id_pagamento' => $forma->id_transacao ?: $pagamento->id,
                    'parcelas' => $forma->parcelas,
                    'clinica' => $prescricao->clinica->nome ?? 'N/A',
                    'medico' => $prescricao->medico,
                    'colaborador' => $pagamento->user->nome ?? 'Não identificado',
                    'situacao_financeira' => $prescricao->situacao_financeira,
                    'obs' => $pagamento->obs,
                ];
            }
        }

        usort($linhas, function ($a, $b) {
            return $a['ordem'] <=> $b['ordem'];
        });

        return $linhas;
    }

    public function exportar_financeiro_simplificado(Request $request)
    {
        $dados = json_decode($request->dados, true) ?? [];
        $linhas = $this->montar_financeiro_simplificado($dados);

        $cabecalho = [
            'ID', 'Data', 'Paciente', 'ID Feegow', 'CPF', 'Código', 'Valor Tratamento',
            'Crédito em Aberto', 'Pagamento', 'Procedimentos', 'Aplicações', 'Atendimento',
            'Forma Pagamento', 'ID Pagamento', 'Parcelas', 'Clínica', 'Médico',
            'Colaborador', 'Situação Financeira', 'Obs',
        ];

        $saida = [];
        foreach ($linhas as $l) {
            $saida[] = [
                $l['pagamento_id'], $l['data'], $l['paciente'], $l['id_feegow'], $l['cpf'],
                $l['codigo'], $l['vl_tratamento'], $l['credito_total'], $l['vl_pagamento'],
                $l['vl_procedimentos'], $l['vl_aplicacoes'], $l['tipo_atendimento'],
                $l['forma_pagamento'], $l['id_pagamento'], $l['parcelas'], $l['clinica'],
                $l['medico'], $l['colaborador'], $l['situacao_financeira'], $l['obs'],
            ];
        }

        return $this->exportar_xlsx('Financeiro_Simplificado_Prescricoes', $cabecalho, $saida);
    }

    /* =====================================================================
     |  VENDAS  (medicamentos/semanas das prescrições)
     ===================================================================== */

    public function vendas()
    {
        $clinicas = Clinica::all()->sortBy('nome');
        $medicamentos = Medicamento::all()->sortBy('nome');
        $medicos = api()->get_medicos();

        return view('adm/relatorios2/vendas', compact('clinicas', 'medicamentos', 'medicos'));
    }

    public function vendas_gerar(Request $request)
    {
        $dados = $this->filtros($request);
        $itens = $this->montar_vendas($dados);

        return view('adm/relatorios2/vendas_gerar', compact('itens', 'dados'));
    }

    private function query_itens(array $dados)
    {
        return PrescricaoSemanaMedicamento::query()
            ->with([
                'medicamento',
                'combo',
                'lotes',
                'userAplicacao',
                'semana.parcela',
                'semana.userAplicacao',
                'semana.prescricao.paciente',
                'semana.prescricao.clinica',
            ])
            ->whereHas('semana.prescricao', function ($q) use ($dados) {
                if (!empty($dados['paciente_id'])) {
                    $q->where('paciente_id', $dados['paciente_id']);
                }
                if (!empty($dados['clinica_id'])) {
                    $q->where('clinica_id', $dados['clinica_id']);
                }
                if (!empty($dados['medico'])) {
                    $q->where('medico', $dados['medico']);
                }
                if (!empty($dados['prescricao_situacao'])) {
                    $q->where('situacao', $dados['prescricao_situacao']);
                }
                if (!empty($dados['situacao_financeira'])) {
                    $q->where('situacao_financeira', $dados['situacao_financeira']);
                }
                if (!empty($dados['dt_inc'])) {
                    $q->where('data_prescricao', '>=', $dados['dt_inc']);
                }
                if (!empty($dados['dt_fn'])) {
                    $q->where('data_prescricao', '<=', $dados['dt_fn']);
                }
            })
            ->when(!empty($dados['medicamento_id']), function ($q) use ($dados) {
                $q->where('medicamento_id', $dados['medicamento_id']);
            })
            ->when(!empty($dados['situacao']), function ($q) use ($dados) {
                $q->where('situacao', $dados['situacao']);
            })
            ->when(!empty($dados['combo_id']), function ($q) use ($dados) {
                $q->whereNotNull('combo_id');
            });
    }

    private function montar_vendas(array $dados)
    {
        $itens = $this->query_itens($dados)->orderBy('id')->get();

        // data do último pagamento de cada prescrição (evita N+1 na view/export)
        $ids = [];
        foreach ($itens as $item) {
            $prescricao = $item->semana->prescricao ?? null;
            if ($prescricao) {
                $ids[] = $prescricao->id;
            }
        }

        $ultimos = [];
        if (count($ids)) {
            $ultimos = PrescricaoPagamento::query()
                ->selectRaw('prescricao_id, MAX(dt_pagamento) as dt_ultimo')
                ->whereIn('prescricao_id', array_unique($ids))
                ->groupBy('prescricao_id')
                ->pluck('dt_ultimo', 'prescricao_id')
                ->all();
        }

        foreach ($itens as $item) {
            $prescricao = $item->semana->prescricao ?? null;
            if ($prescricao) {
                $prescricao->setAttribute('dt_ultimo_pagamento', $ultimos[$prescricao->id] ?? null);
            }
        }

        return $itens;
    }

    public function exportar_vendas(Request $request)
    {
        $dados = json_decode($request->dados, true) ?? [];
        $itens = $this->montar_vendas($dados);

        $cabecalho = [
            'Medicamento', 'Combo', 'Quantidade', 'Status', 'Cadastro', 'Semana',
            'Previsão', 'Aplicação', 'Valor Semana', 'Pago Semana', 'Situação Financeira',
            'Último Pagamento', 'Prescrição', 'Paciente', 'Clínica', 'Médico',
        ];

        $saida = [];
        foreach ($itens as $item) {
            $semana = $item->semana;
            $prescricao = $semana->prescricao ?? null;
            $parcela = $semana->parcela ?? null;

            $saida[] = [
                $item->medicamento->nome ?? 'N/A',
                $item->combo->nome ?? '',
                valorDbForm($item->quantidade),
                $item->situacao,
                $this->data_simples($prescricao->data_prescricao ?? null),
                $semana->nr_semana ?? '',
                $this->data_simples($semana->data_prevista ?? null),
                $this->data_simples($item->aplicado_em ?: ($semana->data_aplicada ?? null)),
                $this->reais($parcela->valor_parcela ?? 0),
                $this->reais($parcela->valor_pago ?? 0),
                $prescricao->situacao_financeira ?? '',
                $this->data_simples($prescricao->dt_ultimo_pagamento ?? null),
                $this->codigo_prescricao($prescricao),
                $prescricao->paciente->nm_paciente ?? 'N/A',
                $prescricao->clinica->nome ?? 'N/A',
                $prescricao->medico ?? '',
            ];
        }

        return $this->exportar_xlsx('Vendas_Prescricoes', $cabecalho, $saida);
    }

    /* =====================================================================
     |  ENFERMAGEM  (aplicações das semanas)
     ===================================================================== */

    public function enfermagem()
    {
        $clinicas = Clinica::all()->sortBy('nome');
        $enfermeiras = $this->enfermeiras();

        return view('adm/relatorios2/enfermagem', compact('clinicas', 'enfermeiras'));
    }

    public function enfermagem_gerar(Request $request)
    {
        $dados = $this->filtros($request);
        $itens = $this->montar_enfermagem($dados);

        return view('adm/relatorios2/enfermagem_gerar', compact('itens', 'dados'));
    }

    private function montar_enfermagem(array $dados)
    {
        $itens = PrescricaoSemanaMedicamento::query()
            ->with([
                'medicamento',
                'lotes',
                'userAplicacao',
                'semana.parcela',
                'semana.userAplicacao',
                'semana.prescricao.paciente',
                'semana.prescricao.clinica',
            ])
            ->where('situacao', 'Aplicada')
            ->whereHas('semana.prescricao', function ($q) use ($dados) {
                if (!empty($dados['paciente_id'])) {
                    $q->where('paciente_id', $dados['paciente_id']);
                }
            })
            // clínica: da aplicação (clinica_id_aplicacao) ou do cadastro da prescrição
            ->when(!empty($dados['clinica_id']), function ($q) use ($dados) {
                $q->where(function ($sub) use ($dados) {
                    $sub->where('clinica_id_aplicacao', $dados['clinica_id'])
                        ->orWhereHas('semana.prescricao', function ($p) use ($dados) {
                            $p->where('clinica_id', $dados['clinica_id']);
                        });
                });
            })
            ->when(!empty($dados['user_id']), function ($q) use ($dados) {
                $q->where('user_id_aplicacao', $dados['user_id']);
            })
            ->when(!empty($dados['dt_inc']), function ($q) use ($dados) {
                $q->where('aplicado_em', '>=', $dados['dt_inc'] . ' 00:00:00');
            })
            ->when(!empty($dados['dt_fn']), function ($q) use ($dados) {
                $q->where('aplicado_em', '<=', $dados['dt_fn'] . ' 23:59:59');
            })
            ->orderBy('aplicado_em')
            ->get();

        // textos de lote / código de barras / validade já resolvidos (evita lógica na view)
        foreach ($itens as $item) {
            $item->setAttribute('lotes_txt', $this->lotes_html($item));
            $item->setAttribute('codigos_txt', $this->codigos_html($item));
            $item->setAttribute('vencimentos_txt', $this->vencimentos_html($item, $item->medicamento_id));
            $item->setAttribute('clinica_aplicacao_nome', $this->clinica_nome($item->clinica_id_aplicacao));
        }

        return $itens;
    }

    public function exportar_enfermagem(Request $request)
    {
        $dados = json_decode($request->dados, true) ?? [];
        $itens = $this->montar_enfermagem($dados);

        $cabecalho = [
            'Chegada', 'Atendimento', 'Tipo', 'Finalização', 'Aplicação', 'Paciente',
            'Enfermeira', 'Clínica', 'Medicamento', 'Quantidade', 'Valor Semana',
            'Lote', 'C. Barras', 'Validade', 'Obs', 'Prescrição', 'Semana',
            'Situação Financeira', 'Coord.', 'Qual.',
        ];

        $saida = [];
        foreach ($itens as $item) {
            $semana = $item->semana;
            $prescricao = $semana->prescricao ?? null;

            $saida[] = [
                $this->data_hora($item->dt_hr_chegada ?: $semana->dt_hr_chegada),
                $this->data_hora($item->dt_hr_atendimento ?: $semana->dt_hr_atendimento),
                $prescricao->tipo_atendimento ?? '',
                $this->data_hora($semana->dt_hr_finalizacao),
                $this->data_hora($item->aplicado_em),
                $prescricao->paciente->nm_paciente ?? 'N/A',
                ($item->userAplicacao->nome ?? ($semana->userAplicacao->nome ?? '')),
                $item->clinica_aplicacao_nome ?: ($prescricao->clinica->nome ?? 'N/A'),
                $item->medicamento->nome ?? 'N/A',
                valorDbForm($item->quantidade),
                $this->reais($semana->parcela->valor_parcela ?? 0),
                strip_tags(str_replace('<br>', ' | ', $item->lotes_txt)),
                strip_tags(str_replace('<br>', ' | ', $item->codigos_txt)),
                strip_tags(str_replace('<br>', ' | ', $item->vencimentos_txt)),
                $item->obs,
                $this->codigo_prescricao($prescricao),
                'Semana ' . ($semana->nr_semana ?? ''),
                $prescricao->situacao_financeira ?? '',
                $semana->flag_coordenacao ? 'Sim' : 'Não',
                $semana->flag_qualidade ? 'Sim' : 'Não',
            ];
        }

        return $this->exportar_xlsx('Enfermagem_Prescricoes', $cabecalho, $saida);
    }

    /* =====================================================================
     |  RECEPÇÃO  (prescrições cadastradas por colaborador)
     ===================================================================== */

    public function recepcao()
    {
        $clinicas = Clinica::all()->sortBy('nome');
        $recepcionistas = User::where('st_usuario', 'Ativo')->orderBy('nome')->get();

        return view('adm/relatorios2/recepcao', compact('clinicas', 'recepcionistas'));
    }

    public function recepcao_gerar(Request $request)
    {
        $dados = $this->filtros($request);
        $prescricoes = $this->montar_recepcao($dados);

        return view('adm/relatorios2/recepcao_gerar', compact('prescricoes', 'dados'));
    }

    private function montar_recepcao(array $dados)
    {
        return $this->query_prescricoes($dados)
            ->with(['paciente', 'clinica', 'userCadastro'])
            ->withCount(['semanas', 'parcelas'])
            ->orderBy('data_prescricao')
            ->orderBy('id')
            ->get();
    }

    public function exportar_recepcao(Request $request)
    {
        $dados = json_decode($request->dados, true) ?? [];
        $prescricoes = $this->montar_recepcao($dados);

        $cabecalho = [
            'Colaborador', 'Paciente', 'Clínica', 'Data Prescrição', 'Cadastro',
            'Semanas', 'Semanas c/ Aplicação', 'Valor Tratamento', 'Situação',
            'Situação Financeira', 'Prescrição',
        ];

        $saida = [];
        foreach ($prescricoes as $prescricao) {
            $saida[] = [
                $prescricao->userCadastro->nome ?? 'N/A',
                $prescricao->paciente->nm_paciente ?? 'N/A',
                $prescricao->clinica->nome ?? 'N/A',
                $this->data_simples($prescricao->data_prescricao),
                $this->data_hora($prescricao->created_at),
                $prescricao->qt_semanas,
                $prescricao->qt_semanas_aplicacao,
                $this->reais($prescricao->valor_tratamento),
                $prescricao->situacao,
                $prescricao->situacao_financeira,
                $this->codigo_prescricao($prescricao),
            ];
        }

        return $this->exportar_xlsx('Recepcao_Prescricoes', $cabecalho, $saida);
    }

    /* =====================================================================
     |  CAIXA  (recebimentos por colaborador no período)
     ===================================================================== */

    public function caixa()
    {
        $clinicas = Clinica::all()->sortBy('nome');
        $usuarios = $this->colaboradores();

        return view('adm/relatorios2/caixa', compact('clinicas', 'usuarios'));
    }

    public function caixa_gerar(Request $request)
    {
        $dados = $this->filtros($request);
        $formas = $this->montar_caixa($dados);
        $user_filtro = !empty($dados['user_id']) ? User::find($dados['user_id']) : null;

        return view('adm/relatorios2/caixa_gerar', compact('formas', 'dados', 'user_filtro'));
    }

    private function montar_caixa(array $dados)
    {
        $prescricoes = $this->query_prescricoes($dados, false)
            ->with(['paciente', 'clinica'])
            ->get();

        if ($prescricoes->isEmpty()) {
            return collect();
        }

        return PrescricaoPagamentoForma::query()
            ->with(['pagamento.user', 'pagamento.prescricao.paciente'])
            ->whereHas('pagamento', function ($q) use ($dados, $prescricoes) {
                $q->whereIn('prescricao_id', $prescricoes->pluck('id')->all());
                if (!empty($dados['dt_inc'])) {
                    $q->where('dt_pagamento', '>=', $dados['dt_inc']);
                }
                if (!empty($dados['dt_fn'])) {
                    $q->where('dt_pagamento', '<=', $dados['dt_fn']);
                }
                if (!empty($dados['user_id'])) {
                    $q->where('user_id', $dados['user_id']);
                }
            })
            ->get()
            ->sortBy(function ($forma) {
                return ($forma->pagamento->dt_pagamento ?? '') . '-' . str_pad((string) $forma->id, 10, '0', STR_PAD_LEFT);
            })
            ->values();
    }

    public function exportar_caixa(Request $request)
    {
        $dados = json_decode($request->dados, true) ?? [];
        $formas = $this->montar_caixa($dados);

        $cabecalho = [
            'Data/Hora', 'Colaborador', 'Paciente', 'Valor Recebido', 'Forma de Pagamento',
            'Nº DOC', 'Clínica', 'Prescrição',
        ];

        $saida = [];
        foreach ($formas as $forma) {
            $pagamento = $forma->pagamento;
            $prescricao = $pagamento->prescricao ?? null;

            $saida[] = [
                $this->data_hora($pagamento->dt_pagamento ?? $pagamento->created_at),
                $pagamento->user->nome ?? 'Não identificado',
                $prescricao->paciente->nm_paciente ?? 'N/A',
                $this->reais($forma->vl_pagamento),
                $forma->forma_pagamento . ($forma->parcelas > 1 ? ' (' . $forma->parcelas . 'x)' : ''),
                $forma->id_transacao ?: $pagamento->id,
                $prescricao->clinica->nome ?? 'N/A',
                $this->codigo_prescricao($prescricao),
            ];
        }

        return $this->exportar_xlsx('Caixa_Prescricoes', $cabecalho, $saida);
    }
}
