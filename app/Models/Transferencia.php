<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'clinica_destino_id',
        'motivo',
        'data',
        'valor',
        'user_id',
    ];

    public function origem(){
        return $this->belongsTo(Clinica::class,'clinica_id','id');
    }

    public function destino(){
        return $this->belongsTo(Clinica::class,'clinica_destino_id','id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function medicamentos($clinica_id){
        return Estoque::where('transferencia_id', $this->id)
        ->where('clinica_id', $clinica_id)
        ->get();
    }

    public static function gerar_relatorio_transferencias($filtros){
        $array = array();
        $sql = "SELECT id FROM transferencias WHERE 1=1";
        if($filtros['dt_inc']){
            $sql .= " AND data>=?";
            $array[] = $filtros['dt_inc'];
        }
        if($filtros['dt_fn']){
            $sql .= " AND data<=?";
            $array[] = $filtros['dt_fn'];
        }
        $sql .= " ORDER BY data";

        $res = \DB::select($sql, $array);

        $in = array();
        foreach($res as $linha){
            $in[] = $linha->id;
        }

        return SELF::whereIn('id', $in)->get();
    }
}
