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
        $medicamentos = Medicamento::where('unidade','<>','Procedimento')->orderBy('nome')->get();
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
            if($quantidade > 0){
                $array_view[] = $array;
            }
        }
        //dd($array_view);

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

        $codigos = Estoque::select('codigo_barras')
        ->where('medicamento_id', $medicamento->id)
        ->distinct()
        ->get();

        foreach($codigos as $linha){
            $estoque = Estoque::where('medicamento_id', $medicamento->id)->where('codigo_barras',$linha->codigo_barras)->first();
            $quantidade = 0;
            $array = [
                'lote' => $estoque->lote,
                'codigo_barras' => $estoque->codigo_barras,
                'data_vencimento' => dataDbForm($estoque->dt_vencimento),
            ];
            //vamos buscar as entradas e saidas de cada clinica
            foreach($clinicas as $clinica){
                $entrada = Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $medicamento->id)
                ->where('codigo_barras',$linha->codigo_barras)
                ->where('tipo','Entrada')
                ->sum('quantidade');

                $saida = Estoque::where('clinica_id', $clinica->id)
                ->where('medicamento_id', $medicamento->id)
                ->where('codigo_barras',$linha->codigo_barras)
                ->where('tipo','Saida')
                ->sum('quantidade');

                $qt_clinica = $entrada - $saida;
                $array[$clinica->nome] = $qt_clinica;
                $quantidade += $qt_clinica;
            }

            $array['quantidade'] = $quantidade;
            if($quantidade > 0){
                $array_view[] = $array;
            }
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

    public function exportar(){
        $medicamentos = Medicamento::where('unidade','<>','Procedimento')->orderBy('nome')->get();
        $clinicas = Clinica::all()->sortBy('nome');
        $array_view = array();

        foreach($medicamentos as $medicamento){
            $quantidade = 0;
            $array = [
                'id' => $medicamento->id,
                'medicamento' => $medicamento->nome,
                'unidade' => $medicamento->unidade,
                'estoque_minimo' => $medicamento->estoque_minimo,
                'negrito' => true,
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

            if($quantidade > 0){
                $array_view[] = $array;
                //vamos colocar os lotes
                $medicamento = Medicamento::where('id', $array['id'])->first();
                $clinicas = Clinica::all()->sortBy('nome');

                $codigos = Estoque::select('codigo_barras')
                ->where('medicamento_id', $medicamento->id)
                ->distinct()
                ->get();

                foreach($codigos as $linha){
                    $estoque = Estoque::where('medicamento_id', $medicamento->id)->where('codigo_barras',$linha->codigo_barras)->first();
                    $quantidade = 0;
                    $array = [
                        'lote' => $estoque->lote,
                        'codigo_barras' => $estoque->codigo_barras,
                        'data_vencimento' => dataDbForm($estoque->dt_vencimento),
                        'negrito' => false,
                    ];
                    //vamos buscar as entradas e saidas de cada clinica
                    foreach($clinicas as $clinica){
                        $entrada = Estoque::where('clinica_id', $clinica->id)
                        ->where('medicamento_id', $medicamento->id)
                        ->where('codigo_barras',$linha->codigo_barras)
                        ->where('tipo','Entrada')
                        ->sum('quantidade');

                        $saida = Estoque::where('clinica_id', $clinica->id)
                        ->where('medicamento_id', $medicamento->id)
                        ->where('codigo_barras',$linha->codigo_barras)
                        ->where('tipo','Saida')
                        ->sum('quantidade');

                        $qt_clinica = $entrada - $saida;
                        $array[$clinica->nome] = $qt_clinica;
                        $quantidade += $qt_clinica;
                    }

                    $array['quantidade'] = $quantidade;
                    if($quantidade > 0){
                        $array_view[] = $array;
                    }
                }
            }
        }

        //vamos verificar o estoque aberto
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

        $html = "
        <table>
            <thead>
                <tr>
                    <td>Estoque</td>
                </tr>
                <tr>
                    <th>Medicamento</th>
                    <th>Unidade</th>
                    <th>Estoque Minimo</th>
                    <th>QT Total</th>
                    ";
                    foreach($clinicas as $clinica){
                        $html .= "<th>$clinica->nome</th>";
                    }
                    $html .= "
                    <th>Lote</th>
                    <th>C.Barras</th>
                    <th>Vencimento</th>
                </tr>
            </thead>
            <tbody>
            ";
            foreach($array_view as $array){
                if($array['negrito']){
                    $array_controle = $array;
                    $html .= "
                        <tr>
                            <td><b>$array[medicamento]</b></td>
                            <td><b>$array[unidade]</b></td>
                            <td><b>$array[estoque_minimo]</b></td>
                            <td><b>$array[quantidade]</b></td>
                            ";
                            foreach($clinicas as $clinica){
                                $html .= "<td><b>".$array[$clinica->nome]."</b></td>";
                            }
                            $html .= "
                        </tr>
                    ";
                }
                else{
                    $html .= "
                        <tr>
                            <td>$array_controle[medicamento]</td>
                            <td>$array_controle[unidade]</td>
                            <td>$array_controle[estoque_minimo]</td>
                            <td>$array[quantidade]</td>
                            ";
                            foreach($clinicas as $clinica){
                                $html .= "<td>".$array[$clinica->nome]."</td>";
                            }
                            $html .= "
                            <td>$array[lote]</td>
                            <td>$array[codigo_barras]</td>
                            <td>$array[data_vencimento]</td>
                        </tr>
                    ";
                }
            }
            $html .= "
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
            </tbody>
        </table>


        <table>
            <thead>
                <tr>
                    <th>Estoques Abertos</th>
                </tr>
                <tr>
                    <th>Clinica</th>
                    <th>Medicamento</th>
                    <th>Abertura</th>
                    <th>Usuário</th>
                    <th>Lote</th>
                    <th>C.Barras</th>
                    <th>Frasco(mg)</th>
                    <th>Restante</th>
                </tr>
            </thead>
            <tbody>
            ";
            foreach($array_abertos as $array){
                $html .= "
                    <tr>
                        <td>$array[clinica]</td>
                        <td>$array[medicamento]</td>
                        <td>$array[abertura]</td>
                        <td>$array[usuario]</td>
                        <td>$array[frasco]</td>
                        <td>$array[restante]</td>
                        <td>$array[lote]</td>
                        <td>$array[codigo_barras]</td>
                    </tr>
                ";
            }
            $html .= "
            </tbody>
        </table>
        ";

        $arquivo = "Exportar Estoque - ".date('d.m.Y - H:i').'.xls';
        $arquivo = str_replace(":",'h',$arquivo);

        // Configurações header para forçar o download
        header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header ("Cache-Control: no-cache, must-revalidate");
        header ("Pragma: no-cache");
        header ("Content-type: application/x-msexcel");
        header ("Content-Disposition: attachment; filename=\"{$arquivo}\"" );
        header ("Content-Description: PHP Generated Data" );
        // Envia o conteúdo do arquivo
        echo $html;
        exit();
    }
}
