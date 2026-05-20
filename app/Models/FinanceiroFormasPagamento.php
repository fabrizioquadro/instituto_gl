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
        ];

        $financeiro = Financeiro::where('id', $this->financeiro_id)->first();
        if (!$financeiro) {
            return $retorno;
        }

        // 1. Descobrir quanto já foi pago até este pagamento
        $pagamento_anterior = SELF::where('financeiro_id', $financeiro->id)
            ->where('created_at', '<', $this->created_at)
            ->sum('vl_pagamento');
        $pagamento_anterior = floatval($pagamento_anterior);

        // 2. Definir os totais de cada caixinha (Consulta, Aplicação, Procedimento)
        $vl_consulta_total = floatval($financeiro->vl_consulta);
        
        $vl_aplicacao_total = floatval($financeiro->valor_aplicacaos());
        $vl_procedimento_total = floatval($financeiro->valor_procedimentos());

        // Se ambos os itens forem 0, caímos no comportamento padrão (tudo vira Aplicação)
        if ($vl_aplicacao_total == 0 && $vl_procedimento_total == 0) {
            $vl_aplicacao_total = floatval($financeiro->vl_procedimentos);
        }

        // 3. Função auxiliar para alocar um valor acumulado nas três caixinhas
        $aloca = function($acumulado) use ($vl_consulta_total, $vl_aplicacao_total, $vl_procedimento_total) {
            $alocado = [
                'consulta' => 0,
                'aplicacao' => 0,
                'procedimento' => 0
            ];

            // Aloca primeiro para Consulta
            if ($acumulado <= $vl_consulta_total) {
                $alocado['consulta'] = $acumulado;
                return $alocado;
            }

            $alocado['consulta'] = $vl_consulta_total;
            $restante = $acumulado - $vl_consulta_total;

            $total_itens = $vl_aplicacao_total + $vl_procedimento_total;
            if ($total_itens <= 0) {
                $alocado['aplicacao'] = $restante;
                return $alocado;
            }

            // Se o restante cobrir tudo das duas caixinhas
            if ($restante >= $total_itens) {
                $alocado['aplicacao'] = $vl_aplicacao_total;
                $alocado['procedimento'] = $vl_procedimento_total;
                
                $sobra = $restante - $total_itens;
                if ($vl_aplicacao_total > 0 && $vl_procedimento_total > 0) {
                    $alocado['aplicacao'] += $sobra * ($vl_aplicacao_total / $total_itens);
                    $alocado['procedimento'] += $sobra * ($vl_procedimento_total / $total_itens);
                } elseif ($vl_aplicacao_total > 0) {
                    $alocado['aplicacao'] += $sobra;
                } else {
                    $alocado['procedimento'] += $sobra;
                }
                return $alocado;
            }

            // Se for parcial
            if ($vl_aplicacao_total > 0 && $vl_procedimento_total > 0) {
                $alocado['aplicacao'] = $restante * ($vl_aplicacao_total / $total_itens);
                $alocado['procedimento'] = $restante * ($vl_procedimento_total / $total_itens);
            } elseif ($vl_aplicacao_total > 0) {
                $alocado['aplicacao'] = $restante;
            } else {
                $alocado['procedimento'] = $restante;
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

        // 6. Preencher o retorno
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
            if (round($aloc_atual['aplicacao'], 2) >= round($vl_aplicacao_total, 2)) {
                $retorno['tipo_aplicacao'] = 'Total';
            } else {
                $retorno['tipo_aplicacao'] = 'Parcial';
            }
        }

        $retorno['vl_procedimento'] = round($vl_procedimento_pag, 2);
        if ($vl_procedimento_pag > 0) {
            if (round($aloc_atual['procedimento'], 2) >= round($vl_procedimento_total, 2)) {
                $retorno['tipo_procedimento'] = 'Total';
            } else {
                $retorno['tipo_procedimento'] = 'Parcial';
            }
        }

        return $retorno;
    }
}
