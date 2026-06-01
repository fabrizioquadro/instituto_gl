<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceiroFormasPagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'financeiro_id',
        'forma_pagamento',
        'parcelas',
        'vl_pagamento',
        'id_pagamento',
        'user_id_cadastro',
    ];

    public function cadastrante(){
        return $this->belongsTo(User::class,'user_id_cadastro','id');
    }

    public function financeiro(){
        return $this->belongsTo(Financeiro::class);
    }

    public function get_rateio_financeiro(){
        $vl_pagamento = floatval($this->vl_pagamento); // valor do pagamento atual

        $retorno = [
            'vl_consulta' => 0,
            'tipo_consulta' => '',
            'vl_aplicacao' => 0,
            'tipo_aplicacao' => '',
            'vl_procedimento' => 0,
            'tipo_procedimento' => '',
            'detalhes_procedimentos' => [],
        ];

        $financeiro = Financeiro::where('id', $this->financeiro_id)->first();
        if (!$financeiro) {
            return $retorno;
        }

        // 1. Descobrir quanto já foi pago até este pagamento (com desempate por ID caso criados no mesmo segundo)
        $pagamento_anterior = SELF::where('financeiro_id', $financeiro->id)
            ->where(function($query) {
                $query->where('created_at', '<', $this->created_at)
                      ->orWhere(function($q) {
                          $q->where('created_at', '=', $this->created_at)
                            ->where('id', '<', $this->id);
                      });
            })
            ->sum('vl_pagamento');
        $pagamento_anterior = floatval($pagamento_anterior);

        // 2. Definir os totais de cada caixinha por procedimento e globais
        $vl_consulta_total = floatval($financeiro->vl_consulta);

        $procedimentos_valores = [];
        $vl_aplicacao_total_global = 0;
        $vl_procedimento_total_global = 0;

        foreach ($financeiro->procedimentos() as $p) {
            $vl_ap = 0;
            $itens_pr = [];
            foreach ($p->aplicacaos as $ap) {
                $unidade = $ap->medicamento ? $ap->medicamento->unidade : '';
                if ($unidade == 'Procedimento') {
                    $nome_medicamento = $ap->medicamento ? $ap->medicamento->nome : 'Procedimento';
                    $itens_pr[] = [
                        'id_aplicacao' => $ap->id,
                        'nome' => $nome_medicamento,
                        'total' => floatval($ap->total)
                    ];
                } else {
                    $vl_ap += floatval($ap->total);
                }
            }

            $vl_pr = 0;
            foreach ($itens_pr as $it) {
                $vl_pr += $it['total'];
            }

            $procedimentos_valores[] = [
                'id' => $p->id,
                'vl_aplicacao' => $vl_ap,
                'vl_procedimento' => $vl_pr,
                'itens_procedimentos' => $itens_pr,
                'vl_total' => $vl_ap + $vl_pr
            ];
            $vl_aplicacao_total_global += $vl_ap;
            $vl_procedimento_total_global += $vl_pr;
        }

        // Se ambos globais forem 0, caímos no comportamento padrão (tudo vira Aplicação)
        if ($vl_aplicacao_total_global == 0 && $vl_procedimento_total_global == 0) {
            $vl_aplicacao_total_global = floatval($financeiro->vl_procedimentos);
        }

        // 3. Função auxiliar para alocar um valor acumulado sequencialmente nos procedimentos
        $aloca = function($acumulado) use ($vl_consulta_total, $procedimentos_valores, $vl_aplicacao_total_global, $vl_procedimento_total_global) {
            $alocado = [
                'consulta' => 0,
                'aplicacao' => 0,
                'procedimento' => 0,
                'detalhes_procedimentos' => []
            ];

            // Inicializa todos os itens de procedimento com 0.0
            foreach ($procedimentos_valores as $pv) {
                foreach ($pv['itens_procedimentos'] as $it) {
                    $alocado['detalhes_procedimentos'][$it['id_aplicacao']] = [
                        'nome' => $it['nome'],
                        'valor' => 0.0
                    ];
                }
            }

            // Aloca primeiro para Consulta
            if ($acumulado <= $vl_consulta_total) {
                $alocado['consulta'] = $acumulado;
                return $alocado;
            }

            $alocado['consulta'] = $vl_consulta_total;
            $restante = $acumulado - $vl_consulta_total;

            // Aloca sequencialmente para cada procedimento (oldest first)
            foreach ($procedimentos_valores as $pv) {
                if ($restante <= 0) {
                    break;
                }

                $vl_total = $pv['vl_total'];
                if ($vl_total <= 0) {
                    continue;
                }

                if ($restante >= $vl_total) {
                    $alocado['aplicacao'] += $pv['vl_aplicacao'];
                    $alocado['procedimento'] += $pv['vl_procedimento'];
                    
                    // Quita todos os itens de procedimento deste procedimento
                    foreach ($pv['itens_procedimentos'] as $it) {
                        $alocado['detalhes_procedimentos'][$it['id_aplicacao']]['valor'] = $it['total'];
                    }

                    $restante -= $vl_total;
                } else {
                    // Distribui o restante PRIORIZANDO a categoria "Procedimento" primeiro
                    $vl_pr = $pv['vl_procedimento'];

                    if ($restante <= $vl_pr) {
                        // O restante não cobre nem todo o Procedimento
                        $alocado['procedimento'] += $restante;

                        // Aloca sequencialmente aos itens individuais de procedimento
                        $temp_rest = $restante;
                        foreach ($pv['itens_procedimentos'] as $it) {
                            if ($temp_rest <= 0) {
                                break;
                            }
                            if ($temp_rest >= $it['total']) {
                                $alocado['detalhes_procedimentos'][$it['id_aplicacao']]['valor'] = $it['total'];
                                $temp_rest -= $it['total'];
                            } else {
                                $alocado['detalhes_procedimentos'][$it['id_aplicacao']]['valor'] = $temp_rest;
                                $temp_rest = 0;
                                break;
                            }
                        }
                    } else {
                        // Cobre todo o Procedimento e parte da Aplicação
                        $alocado['procedimento'] += $vl_pr;
                        $alocado['aplicacao'] += ($restante - $vl_pr);

                        // Quita todos os itens de procedimento deste procedimento
                        foreach ($pv['itens_procedimentos'] as $it) {
                            $alocado['detalhes_procedimentos'][$it['id_aplicacao']]['valor'] = $it['total'];
                        }
                    }
                    $restante = 0;
                    break;
                }
            }

            // Se ainda restar saldo após pagar todos os procedimentos (ex: arredondamento ou valor excedente)
            if ($restante > 0) {
                $alocado['aplicacao'] += $restante;
            }

            return $alocado;
        };

        // 4. Calcular alocação para o acumulado anterior e para o acumulado atual
        $acumulado_anterior = $pagamento_anterior;
        $acumulado_atual = $pagamento_anterior + $vl_pagamento;

        $aloc_anterior = $aloca($acumulado_anterior);
        $aloc_atual = $aloca($acumulado_atual);

        // 5. O pagamento atual recebe a diferença entre o atual e o anterior
        $vl_consulta_pag = max(0.0, $aloc_atual['consulta'] - $aloc_anterior['consulta']);
        $vl_aplicacao_pag = max(0.0, $aloc_atual['aplicacao'] - $aloc_anterior['aplicacao']);
        $vl_procedimento_pag = max(0.0, $aloc_atual['procedimento'] - $aloc_anterior['procedimento']);

        // 6. Preencher o retorno básico
        $retorno['vl_consulta'] = round($vl_consulta_pag, 2);
        if ($vl_consulta_pag > 0) {
            if (round($aloc_atual['consulta'], 2) >= round($vl_consulta_total, 2)) {
                $retorno['tipo_consulta'] = 'Total';
            } else {
                $retorno['tipo_consulta'] = 'Parcial';
            }
        }

        $retorno['vl_aplicacao'] = round($vl_aplicacao_pag, 2);
        if ($vl_aplicacao_pag > 0) {
            if (round($aloc_atual['aplicacao'], 2) >= round($vl_aplicacao_total_global, 2)) {
                $retorno['tipo_aplicacao'] = 'Total';
            } else {
                $retorno['tipo_aplicacao'] = 'Parcial';
            }
        }

        $retorno['vl_procedimento'] = round($vl_procedimento_pag, 2);
        if ($vl_procedimento_pag > 0) {
            if (round($aloc_atual['procedimento'], 2) >= round($vl_procedimento_total_global, 2)) {
                $retorno['tipo_procedimento'] = 'Total';
            } else {
                $retorno['tipo_procedimento'] = 'Parcial';
            }
        }

        // 7. Detalhar cada item de procedimento com seu valor individual do pagamento atual
        foreach ($aloc_atual['detalhes_procedimentos'] as $id_ap => $det) {
            $prev_val = isset($aloc_anterior['detalhes_procedimentos'][$id_ap]) ? $aloc_anterior['detalhes_procedimentos'][$id_ap]['valor'] : 0.0;
            $diff_val = max(0.0, $det['valor'] - $prev_val);
            if (round($diff_val, 2) > 0) {
                $retorno['detalhes_procedimentos'][] = [
                    'nome' => $det['nome'],
                    'valor' => round($diff_val, 2)
                ];
            }
        }

        return $retorno;
    }
}
