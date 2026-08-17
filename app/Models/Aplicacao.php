<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aplicacao extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedimento_id',
        'medicamento_id',
        'user_id_aplicacao',
        'quantidade',
        'valor',
        'total',
        'situacao',
        'obs',
        'is_soro',
        'dt_hr_chegada',
        'dt_hr_atendimento',
    ];

    public function medicamento(){
        return $this->belongsTo(Medicamento::class);
    }

    public function enfermeira(){
        return $this->belongsTo(User::class,'user_id_aplicacao','id');
    }

    public function lote(){
        return $this->hasOne(AplicacaoLote::class);
    }

    public function aplicacaoLotes(){
        return $this->hasMany(AplicacaoLote::class, 'aplicacao_id', 'id');
    }

    public function lotes(){
        $lotes = $this->aplicacaoLotes;
        $retorno = "";
        if($lotes->count() > 1){
            foreach ($lotes as $lote){
                $retorno .= "<br>Lote: ".$lote->lote.", Qtd: ".$lote->quantidade;
            }
            $retorno = substr($retorno, 4);
        }
        else{
            foreach ($lotes as $lote){
                $retorno .= "Lote: ".$lote->lote;
            }
        }
        return $retorno;
    }

    public function codigos(){
        $lotes = $this->aplicacaoLotes;
        $retorno = "";
        if($lotes->count() > 1){
            foreach ($lotes as $lote){
                $retorno .= "<br>Codigo: ".$lote->codigo_barras.", Qtd: ".$lote->quantidade;
            }
            $retorno = substr($retorno, 4);
        }
        else{
            foreach ($lotes as $lote){
                $retorno .= "Codigo: ".$lote->codigo_barras;
            }
        }
        return $retorno;
    }

    public function vencimentos(){
        $lotes = $this->aplicacaoLotes;
        $retorno = "";
        if($lotes->count() > 1){
            foreach ($lotes as $lote){
                $estoque = $lote->estoque;
                if($estoque){
                    $retorno .= "<br>Codigo: ".$lote->codigo_barras.", ".dataDbForm($estoque->dt_vencimento);
                }
            }
            $retorno = substr($retorno, 4);
        }
        else{
            foreach ($lotes as $lote){
                $estoque = $lote->estoque;
                if($estoque){
                    $retorno .= dataDbForm($estoque->dt_vencimento);
                }
            }
        }
        return $retorno;
    }


}
