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
        'vl_adicional',
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

        return Procedimento::whereIn('id', $in)->orderBy('nr_procedimento')->get();
    }

    public function formas(){
        return $this->hasMany(FinanceiroFormasPagamento::class);
    }

    public static function get_pagamentos_relatorio($filtros){
        $where = array();
        if($filtros['paciente_id']){
            $where[] = [
                'paciente_id',
                '=',
                $filtros['paciente_id']
            ];
        }

        if($filtros['clinica_id']){
            $where[] = [
                'clinica_id',
                '=',
                $filtros['clinica_id']
            ];
        }

        if($filtros['dt_inc'] || $filtros['dt_fn']){
            $res = Financeiro::select('id')
            ->where($where)
            ->whereHas('formas', function($query) use ($filtros){
                if($filtros['dt_inc']){
                    $query->where('created_at','>=',$filtros['dt_inc']." 00:00:00");
                }
                if($filtros['dt_fn']){
                    $query->where('created_at','<=',$filtros['dt_fn']." 23:59:59");
                }
            })
            ->distinct()
            ->get();
        }
        else{
            $res = Financeiro::select('id')
            ->where($where)
            ->distinct()
            ->get();
        }

        $in = array();
        foreach($res as $linha){
            $in[] = $linha->id;
        }

        return SELF::whereIn('id', $in)->get();
    }

    public function valor_procedimentos(){
        $in_proc = array();
        foreach($this->procedimentos() as $procedimento){
            $in_proc[] = $procedimento->id;
        }

        if(count($in_proc) > 0){
            $in_proc = implode(',', $in_proc);

            $sql = "SELECT SUM(total) AS total FROM aplicacaos, medicamentos WHERE
            aplicacaos.procedimento_id IN ($in_proc) AND
            aplicacaos.medicamento_id=medicamentos.id AND
            medicamentos.unidade='Procedimento'";

            $res = \DB::select($sql);
            return $res[0]->total;
        }
        else{
            return 0;
        }
    }

    public function valor_aplicacaos(){
        $in_proc = array();
        foreach($this->procedimentos() as $procedimento){
            $in_proc[] = $procedimento->id;
        }
        if(count($in_proc) > 0){
            $in_proc = implode(',', $in_proc);

            $sql = "SELECT SUM(total) AS total FROM aplicacaos, medicamentos WHERE
            aplicacaos.procedimento_id IN ($in_proc) AND
            aplicacaos.medicamento_id=medicamentos.id AND
            medicamentos.unidade<>'Procedimento'";

            $res = \DB::select($sql);
            return $res[0]->total;
        }
        else{
            return 0;
        }
    }

}
