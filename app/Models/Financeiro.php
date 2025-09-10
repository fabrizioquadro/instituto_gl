<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Financeiro extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'paciente_id',
        'medico',
        'dt_pagamento',
        'vl_consulta',
        'vl_consulta_pagamento',
        'vl_procedimentos',
        'vl_desconto',
        'vl_pagamento',
        'tipo_pagamento',
        'forma_pagamento',
        'parcelas',
        'obs_pagamento',
    ];

    public function paciente(){
        return $this->belongsTo(Paciente::class);
    }

    public function clinica(){
        return $this->belongsTo(Clinica::class);
    }

    public function procedimentos(){
        $result = FinanceiroProcedimento::where('financeiro_id', $this->id)->get();
        $in = array();
        foreach($result as $linha){
            $in[] = $linha->procedimento_id;
        }

        return Procedimento::whereIn('id', $in)->get();
    }

    public function formas(){
        return $this->hasMany(FinanceiroFormasPagamento::class);
    }


}
