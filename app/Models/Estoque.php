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
        'procedimento_id',
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

    public function clinica(){
        return $this->belongsTo(Clinica::class);
    }

    public function baixa(){
        return $this->belongsTo(Baixa::class);
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

    public static function get_codigos_medicamento($medicamento_id, $clinica_id){
        $codigos = SELF::select('codigo_barras')
        ->where('clinica_id', $clinica_id)
        ->where('medicamento_id', $medicamento_id)
        ->distinct()
        ->get();

        $array_retorno = array();
        foreach($codigos as $linha){
            $codigo = $linha->codigo_barras;

            //vamos calcular as entradas nesse codigo e nessa clinica
            $entrada = SELF::where('clinica_id', $clinica_id)
            ->where('medicamento_id', $medicamento_id)
            ->where('codigo_barras',$codigo)
            ->where('tipo','Entrada')
            ->sum('quantidade');

            $saida = SELF::where('clinica_id', $clinica_id)
            ->where('medicamento_id', $medicamento_id)
            ->where('codigo_barras',$codigo)
            ->where('tipo','Saida')
            ->sum('quantidade');

            $estoque = $entrada - $saida;
            if($estoque > 0){
                $array = [
                    'lote' => $linha->lote,
                    'codigo_barras' => $linha->codigo_barras,
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

    public static function get_saldo_med_cb_clinica($codigo_barras, $clinica_id){
        //vamos calcular as entradas nesse codigo e nessa clinica
        $entrada = SELF::where('clinica_id', $clinica_id)
        ->where('codigo_barras',$codigo_barras)
        ->where('tipo','Entrada')
        ->sum('quantidade');

        $saida = SELF::where('clinica_id', $clinica_id)
        ->where('codigo_barras',$codigo_barras)
        ->where('tipo','Saida')
        ->sum('quantidade');

        return $entrada - $saida;
    }

    public static function get_medicamentos_vencimento($clinica_id){
        $itens = SELF::select('medicamento_id','lote','codigo_barras','dt_vencimento')
        ->where('clinica_id', $clinica_id)
        ->where('tipo','Entrada')
        ->whereNotNull('dt_vencimento')
        ->distinct()
        ->get();

        $array_vencimento = array();
        foreach($itens as $item){
            $entrada = SELF::where('clinica_id', $clinica_id)
            ->where('codigo_barras',$item->codigo_barras)
            ->where('tipo','Entrada')
            ->sum('quantidade');

            $saida = SELF::where('clinica_id', $clinica_id)
            ->where('codigo_barras',$item->codigo_barras)
            ->where('tipo','Saida')
            ->sum('quantidade');

            $estoque = $entrada - $saida;
            if($estoque > 0){
                $hoje = strtotime(date('Y-m-d'));
                $vencimento = strtotime($item->dt_vencimento);
                $diferenca = ($vencimento - $hoje) / (60 * 60 * 24);

                if($diferenca <= 90){
                    $array = [
                        'medicamento' => $item->medicamento->nome,
                        'lote' => $item->lote,
                        'codigo_barras' => $item->codigo_barras,
                        'dt_vencimento' => $item->dt_vencimento,
                        'dias' => (int)$diferenca,
                        'quantidade' => (float)$estoque
                    ];
                    $array_vencimento[] = $array;
                }
            }
        }
        
        // Ordenar por data de vencimento (mais próximos primeiro)
        usort($array_vencimento, function($a, $b) {
            return strtotime($a['dt_vencimento']) <=> strtotime($b['dt_vencimento']);
        });

        return $array_vencimento;
    }
}
