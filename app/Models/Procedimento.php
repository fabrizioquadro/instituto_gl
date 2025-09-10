<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procedimento extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nr_procedimento',
        'clinica_id',
        'clinica_id_aplicacao',
        'paciente_id',
        'user_id_aplicacao',
        'data_cad',
        'data_aplicacao',
        'data_pagamento',
        'valor',
        'st_pagamento',
        'situacao',
        'medico',
        'obs',
        'tipo_pagamento',
        'forma_pagamento',
        'parcelas',
        'vl_pago',
        'obs_pagamento',
        'st_biopedancia',
        'obs_biopedancia',
        'st_coleta',
        'tp_coleta',
        'obs_coleta',
        'semana_sem_aplicacao',
        'autorizador_sem_pagamento',
        'consulta_tratamento_agendada',
    ];

    public function aplicacaos(){
        return $this->hasMany(Aplicacao::class);
    }

    public function clinica(){
        return $this->belongsTo(Clinica::class);
    }

    public function clinica_aplicacao(){
        return $this->belongsTo(Clinica::class,'clinica_id_aplicacao','id');
    }

    public function anexos(){
        return $this->hasMany(ProcedimentoAnexo::class);
    }

    public function paciente(){
        return $this->belongsTo(Paciente::class);
    }

    public function get_nr_semanas(){
        return SELF::where('codigo', $this->codigo)->count();
    }

    public function get_st_pagamento(){
        $qt_semanas_pgt = SELF::where('codigo', $this->codigo)
        ->where(function ($query){
            $query->where('semana_sem_aplicacao','Não')
            ->orWhereNull('semana_sem_aplicacao');
        })
        ->count();

        $qt_semanas_pagas = SELF::where('codigo', $this->codigo)
        ->where(function ($query){
            $query->where('semana_sem_aplicacao','Não')
            ->orWhereNull('semana_sem_aplicacao');
        })
        ->where('st_pagamento','Sim')
        ->count();

        if($qt_semanas_pagas == 0){
            return 'Aberto';
        }
        elseif($qt_semanas_pagas == $qt_semanas_pgt){
            return 'Total';
        }
        else{
            return 'Parcial';
        }
    }

    public function get_st_procedimento(){
        $qt_situacao = SELF::where('codigo', $this->codigo)
        ->where(function ($query){
            $query->where('semana_sem_aplicacao','Não')
            ->orWhereNull('semana_sem_aplicacao');
        })
        ->where('situacao','<>','Aplicado')
        ->get();

        if($qt_situacao->count() > 0){
            return 'Aberto';
        }
        else{
            return 'Finalizado';
        }
    }

    public function get_st_procedimento_iniciado(){
        $qt_situacao = SELF::where('codigo', $this->codigo)
        ->where(function ($query){
            $query->where('semana_sem_aplicacao','Não')
            ->orWhereNull('semana_sem_aplicacao');
        })
        ->where('situacao','Aplicado')
        ->get();

        if($qt_situacao->count() > 0){
            return true;
        }
        else{
            return false;
        }
    }

    public static function lista_procedimentos_filtro($pesquisar){
        $sql = "SELECT id FROM procedimentos WHERE 1=1";
        if($pesquisar['paciente_id']){
            $sql .= " AND paciente_id='$pesquisar[paciente_id]'";
        }
        if($pesquisar['dt_procedimentos']){
            $sql .= " AND data_aplicacao='$pesquisar[dt_procedimentos]'";
        }
        if($pesquisar['situacao']){
            $sql .= " AND situacao='$pesquisar[situacao]'";
        }
        if($pesquisar['st_pagamento']){
            $sql .= " AND st_pagamento='$pesquisar[st_pagamento]'";
        }

        $result = \DB::select($sql);

        $in = array();
        foreach($result as $linha){
            $in[] = $linha->id;
        }

        return SELF::whereIn('id', $in)->get();
    }
}
