<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medicamento;
use App\Models\Estoque;
use App\Models\EstoqueAberto;
use App\Models\Clinica;

class EstoqueAdmController extends Controller
{
    public function index(){
        $medicamentos = Medicamento::all()->sortBy('nome');
        $clinicas = Clinica::all()->sortBy('nome');
        $array_view = array();

        foreach($medicamentos as $medicamento){
            $quantidade = 0;
            $array = [
                'id' => $medicamento->id,
                'medicamento' => $medicamento->nome,
                'unidade' => $medicamento->unidade,
                'estoque_minimo' => $medicamento->estoque_minimo,
            ];

            //vamos buscar as entradas e saidas de cada clinica
            foreach($clinicas as $clinica){
                $entrada = Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $medicamento->id)
                ->where('tipo','Entrada')
                ->sum('quantidade');

                $saida = Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $medicamento->id)
                ->where('tipo','Saida')
                ->sum('quantidade');

                $qt_clinica = $entrada - $saida;
                $array[$clinica->nome] = $qt_clinica;
                $quantidade += $qt_clinica;
            }

            $array['quantidade'] = $quantidade;
            $array_view[] = $array;

        }

        //vamos buscar os lotes abertos
        $array_abertos = array();
        foreach($clinicas as $clinica){
            $estoques = EstoqueAberto::where('clinica_id', $clinica->id)
            ->where('situacao','Aberto')
            ->get();
            foreach($estoques as $estoque){
                $array = [
                    'clinica' => $clinica->nome,
                    'medicamento' => $estoque->medicamento->nome,
                    'abertura' => dataDbForm($estoque->dt_cadastro),
                    'usuario' => $estoque->user->nome,
                    'frasco' => $estoque->qt_inical." (mg)",
                    'restante' => $estoque->qt_restante." (mg)",
                    'lote' => $estoque->lote,
                    'codigo_barras' => $estoque->codigo_barras,
                ];
                $array_abertos[] = $array;
            }
        }
        return view('adm/estoques/index', compact('array_view','clinicas','array_abertos'));
    }

    public function get_lotes_medicamento(){
        $medicamento = Medicamento::where('id', $_GET['medicamento_id'])->first();
        $clinicas = Clinica::all()->sortBy('nome');
        
        $lotes = Estoque::select('lote')
        ->where('medicamento_id', $medicamento->id)
        ->distinct()
        ->get();

        foreach($lotes as $linha){
            $estoque = Estoque::where('medicamento_id', $medicamento->id)->where('lote',$linha->lote)->first();
            $quantidade = 0;
            $array = [
                'lote' => $linha->lote,
                'codigo_barras' => $estoque->codigo_barras,
                'data_vencimento' => dataDbForm($estoque->dt_vencimento),
            ];
            //vamos buscar as entradas e saidas de cada clinica
            foreach($clinicas as $clinica){
                $entrada = Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $medicamento->id)
                ->where('lote',$linha->lote)
                ->where('tipo','Entrada')
                ->sum('quantidade');

                $saida = Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $medicamento->id)
                ->where('lote',$linha->lote)
                ->where('tipo','Saida')
                ->sum('quantidade');

                $qt_clinica = $entrada - $saida;
                $array[$clinica->nome] = $qt_clinica;
                $quantidade += $qt_clinica;
            }

            $array['quantidade'] = $quantidade;
            $array_view[] = $array;
        }

        $html = "";
        foreach($array_view as $array){
            $html .= "
            <tr>
                <td>".$array['lote']."</td>
                <td>".$array['codigo_barras']."</td>
                <td>".$array['data_vencimento']."</td>
                <td>".$array['quantidade']."</td>";

                foreach($clinicas as $clinica){
                    $html .= "<td>".$array[$clinica->nome]."</td>";
                }

                $html .= "
            </tr>
            ";
        }
        $retorno['medicamento_nome'] = $medicamento->nome;
        $retorno['html'] = $html;
        echo json_encode($retorno);
    }

    public function index_old(){
        $medicamentos = Medicamento::all()->sortBy('nome');
        $clinicas = Clinica::all()->sortBy('nome');
        $array_view = array();

        foreach($medicamentos as $medicamento){
            $lotes = Estoque::select('lote')
            ->where('medicamento_id', $medicamento->id)
            ->distinct()
            ->get();

            foreach($lotes as $linha){
                $estoque = Estoque::where('medicamento_id', $medicamento->id)->where('lote',$linha->lote)->first();
                $quantidade = 0;
                $array = [
                    'medicamento' => $medicamento->nome,
                    'unidade' => $medicamento->unidade,
                    'lote' => $linha->lote,
                    'codigo_barras' => $estoque->codigo_barras,
                    'estoque_minimo' => $medicamento->estoque_minimo,
                    'data_vencimento' => dataDbForm($estoque->dt_vencimento),
                ];
                //vamos buscar as entradas e saidas de cada clinica
                foreach($clinicas as $clinica){
                    $entrada = Estoque::where('clinica_id', $clinica->id)
                    ->where('medicamento_id', $medicamento->id)
                    ->where('lote',$linha->lote)
                    ->where('tipo','Entrada')
                    ->sum('quantidade');

                    $saida = Estoque::where('clinica_id', $clinica->id)
                    ->where('medicamento_id', $medicamento->id)
                    ->where('lote',$linha->lote)
                    ->where('tipo','Saida')
                    ->sum('quantidade');

                    $qt_clinica = $entrada - $saida;
                    $array[$clinica->nome] = $qt_clinica;
                    $quantidade += $qt_clinica;
                }

                $array['quantidade'] = $quantidade;
                $array_view[] = $array;
            }
        }

        //vamos buscar os lotes abertos
        $array_abertos = array();
        foreach($clinicas as $clinica){
            $estoques = EstoqueAberto::where('clinica_id', $clinica->id)
            ->where('situacao','Aberto')
            ->get();
            foreach($estoques as $estoque){
                $array = [
                    'clinica' => $clinica->nome,
                    'medicamento' => $estoque->medicamento->nome,
                    'abertura' => dataDbForm($estoque->dt_cadastro),
                    'usuario' => $estoque->user->nome,
                    'frasco' => $estoque->qt_inical." (mg)",
                    'restante' => $estoque->qt_restante." (mg)",
                    'lote' => $estoque->lote,
                    'codigo_barras' => $estoque->codigo_barras,
                ];
                $array_abertos[] = $array;
            }
        }
        return view('adm/estoques/index', compact('array_view','clinicas','array_abertos'));
    }
}
