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
        'dt_hr_chegada',
        'dt_hr_atendimento',
        'dt_hr_finalizacao',
        'user_id_cadastro',
        'flag_coordenacao',
        'flag_qualidade',
        'agendamento',
        'st_retirada',
        'obs_retirada',
        'tipo_atendimento',
        'user_nome_coordenacao',
        'user_nome_qualidade',
        'inicio_cadastro',
        'finalizacao_cadastro',
    ];


    public static function index_pesq($requestData){

        $procedimentos = SELF::where('nr_procedimento','1')
        ->orderByDesc('data_cad')
        ->get();

        $totalFiltered = count($procedimentos);

        $procedimentos = SELF::where('nr_procedimento','1')
        ->offset($requestData['start'])
        ->limit($requestData['length'])
        ->orderByDesc('data_cad')
        ->get();

        if( !empty($requestData['search']['value']) ) {
            $pacientes = Paciente::where('nm_paciente','LIKE',"%".$requestData['search']['value']."%")->get();
            $in = array();
            foreach($pacientes as $paciente){
                $in[] = $paciente->id;
            }

            $procedimentos = SELF::where('nr_procedimento','1')
            ->where(function($query) use ($requestData, $in){
                $query->where('medico','LIKE','%'.$requestData['search']['value'].'%')
                ->orWhere('codigo',$requestData['search']['value'])
                ->orWhereIn('paciente_id', $in);
            })
            ->orderByDesc('data_cad')
            ->get();

            $totalFiltered = count($procedimentos);

            $procedimentos = SELF::where('nr_procedimento','1')
            ->where(function($query) use ($requestData, $in){
                $query->where('medico','LIKE','%'.$requestData['search']['value'].'%')
                ->orWhere('codigo','LIKE','%'.$requestData['search']['value'].'%')
                ->orWhereIn('paciente_id', $in);
            })
            ->offset($requestData['start'])
            ->limit($requestData['length'])
            ->orderByDesc('data_cad')
            ->get();
        }

        $retorno['procedimentos'] = $procedimentos;
        $retorno['totalFiltered'] = $totalFiltered;

        return $retorno;
    }

    public function aplicacaos(){
        return $this->hasMany(Aplicacao::class);
    }

    public function logs(){
        return $this->hasMany(ProcedimentoLog::class);
    }

    public function observacoes_procedimento(){
        return $this->hasMany(ProcedimentoObservacao::class);
    }

    public function clinica(){
        return $this->belongsTo(Clinica::class);
    }

    public function cadastrante(){
        return $this->belongsTo(User::class,'user_id_cadastro','id');
    }

    public function aplicadora(){
        return $this->belongsTo(User::class,'user_id_aplicacao','id');
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
        ->where('situacao','<>','Cancelado')
        ->get();

        if($qt_situacao->count() > 0){
            return 'Aberto';
        }
        else{
            $qt_situacao = SELF::where('codigo', $this->codigo)
            ->where(function ($query){
                $query->where('semana_sem_aplicacao','Não')
                ->orWhereNull('semana_sem_aplicacao');
            })
            ->where('situacao','Cancelado')
            ->get();
            if($qt_situacao->count() > 0){
                return 'Cancelado';
            }
            else{
                return 'Finalizado';
            }
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
        if(isset($pesquisar['tipo_atendimento']) && $pesquisar['tipo_atendimento']){
            $sql .= " AND tipo_atendimento='$pesquisar[tipo_atendimento]'";
        }

        $result = \DB::select($sql);

        $in = array();
        foreach($result as $linha){
            $in[] = $linha->id;
        }

        return SELF::whereIn('id', $in)->get();
    }

    public static function gerar_relatorio_vendas($filtro){
        $array = array();
        $sql = "SELECT id FROM procedimentos WHERE 1=1";

        if($filtro['clinica_id']){
            $sql .= " AND clinica_id=?";
            $array[] = $filtro['clinica_id'];
        }

        if($filtro['paciente_id']){
            $sql .= " AND paciente_id=?";
            $array[] = $filtro['paciente_id'];
        }

        if($filtro['medico']){
            $sql .= " AND medico=?";
            $array[] = $filtro['medico'];
        }

        if($filtro['dt_inc']){
            $sql .= " AND data_cad>=?";
            $array[] = $filtro['dt_inc'];
        }

        if($filtro['dt_fn']){
            $sql .= " AND data_cad<=?";
            $array[] = $filtro['dt_fn'];
        }

        $sql .= " ORDER BY data_cad";

        $res = \DB::select($sql, $array);

        $in = array();
        foreach($res as $linha){
            $in[] = $linha->id;
        }

        return SELF::whereIn('id', $in)->get();
    }

    public static function gerar_relatorio_enfermagem($filtro){
        $array = array();
        $sql = "SELECT DISTINCT p.id FROM procedimentos p 
                JOIN aplicacaos a ON a.procedimento_id = p.id 
                WHERE a.situacao = 'Aplicada'";

        if($filtro['clinica_id']){
            $sql .= " AND p.clinica_id_aplicacao=?";
            $array[] = $filtro['clinica_id'];
        }

        if($filtro['paciente_id']){
            $sql .= " AND p.paciente_id=?";
            $array[] = $filtro['paciente_id'];
        }

        if($filtro['dt_inc']){
            $sql .= " AND a.updated_at>=?";
            $array[] = $filtro['dt_inc'] . " 00:00:00";
        }

        if($filtro['dt_fn']){
            $sql .= " AND a.updated_at<=?";
            $array[] = $filtro['dt_fn'] . " 23:59:59";
        }

        $res = \DB::select($sql, $array);

        $in = array();
        foreach($res as $linha){
            $in[] = $linha->id;
        }

        return SELF::whereIn('id', $in)->get();
    }


    public function get_ultima_edicao(){
        return ProcedimentoLog::whereIn('procedimento_id', function($query){
            $query->select('id')->from('procedimentos')->where('codigo', $this->codigo);
        })->max('created_at');
    }

}
