<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'entrada_id',
        'baixa_id',
        'transferencia_id',
        'medicamento_id',
        'origem',
        'tipo',
        'quantidade',
        'valor',
        'total',
        'lote',
        'dt_vencimento',
        'codigo_barras',
    ];

    public function medicamento(){
        return $this->belongsTo(Medicamento::class);
    }

    public static function get_lotes_medicamento($medicamento_id, $clinica_id){
        $lotes = SELF::select('lote')
        ->where('clinica_id', $clinica_id)
        ->where('medicamento_id', $medicamento_id)
        ->distinct()
        ->get();

        $array_retorno = array();
        foreach($lotes as $linha){
            $lote = $linha->lote;
            //vamos calcular as entradas nesse lote e nessa clinica
            $entrada = SELF::where('clinica_id', $clinica_id)
            ->where('medicamento_id', $medicamento_id)
            ->where('lote',$lote)
            ->where('tipo','Entrada')
            ->sum('quantidade');

            $saida = SELF::where('clinica_id', $clinica_id)
            ->where('medicamento_id', $medicamento_id)
            ->where('lote',$lote)
            ->where('tipo','Saida')
            ->sum('quantidade');

            $estoque = $entrada - $saida;
            if($estoque > 0){
                $array = [
                    'lote' => $lote,
                    'estoque' => $estoque,
                ];
                $array_retorno[] = $array;
            }
        }
        return $array_retorno;
    }

    public static function get_lotes_medicamento_mg($medicamento_id, $clinica_id){
        $codigos = SELF::select('codigo_barras')
        ->where('clinica_id', $clinica_id)
        ->where('medicamento_id', $medicamento_id)
        ->distinct()
        ->get();

        $array_retorno = array();
        foreach($codigos as $linha){
            $codigo_barras = $linha->codigo_barras;
            //vamos calcular as entradas nesse codigo e nessa clinica
            $entrada = SELF::where('clinica_id', $clinica_id)
            ->where('medicamento_id', $medicamento_id)
            ->where('codigo_barras',$codigo_barras)
            ->where('tipo','Entrada')
            ->sum('quantidade');

            $saida = SELF::where('clinica_id', $clinica_id)
            ->where('medicamento_id', $medicamento_id)
            ->where('codigo_barras',$codigo_barras)
            ->where('tipo','Saida')
            ->sum('quantidade');

            $estoque = $entrada - $saida;
            if($estoque > 0){
                $array = [
                    'codigo_barras' => $codigo_barras,
                    'estoque' => $estoque,
                ];
                $array_retorno[] = $array;
            }
        }
        return $array_retorno;
    }
}
