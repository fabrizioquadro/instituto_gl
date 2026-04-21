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

    public function get_rateio_financeiro(){
        $vl_pagamento = $this->vl_pagamento; //valor do pagamento atual

        $retorno = [
            'vl_consulta' => 0,
            'tipo_consulta' => '',
            'vl_aplicacao' => 0,
            'tipo_aplicacao' => '',
        ];

        $financeiro = Financeiro::where('id', $this->financeiro_id)->first();

        //vamos descobrir quanto já foi pago ate este pagamento
        $pagamento_anterior = SELF::where('financeiro_id', $financeiro->id)
        ->where('created_at','<', $this->created_at)
        ->sum('vl_pagamento');

        $vl_consulta = $financeiro->vl_consulta;
        //vamos ver se o pagamento anterior cobre a consulta
        if($pagamento_anterior >= $vl_consulta){
            $pagamento_anterior -= $vl_consulta;
            $vl_consulta = 0;
        }
        else{
            $vl_consulta -= $pagamento_anterior;
            $pagamento_anterior = 0;
        }

        if($vl_consulta > 0){
            $retorno['vl_consulta'] = $vl_consulta;

            if($vl_pagamento >= $vl_consulta){
                $vl_pagamento -= $vl_consulta;
                $retorno['tipo_consulta'] = 'Total';
            }
            else{
                $retorno['tipo_consulta'] = 'Parcial';
                return $retorno;
                exit();
            }
        }

        //se chegar aqui é que ou não tinha cosulta ou pagou a consulta e sobrou
        //vamos ver o valor que sobra paga todos os procedimentos ou parte
        $vl_total_procedimentos = 0;

        foreach($financeiro->procedimentos() as $proc){
            $vl_total_procedimentos += $proc->valor;
        }

        $retorno['vl_aplicacao'] = $vl_pagamento;
        if(($vl_pagamento + $financeiro->vl_desconto) < $vl_total_procedimentos){
            $retorno['tipo_aplicacao'] = 'Parcial';
        }
        else{
            $retorno['tipo_aplicacao'] = 'Total';
        }
        return $retorno;

    }
}
