<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medicamento;
use App\Models\Estoque;
use App\Models\EstoqueAberto;
use App\Models\Clinica;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

            $tem_estoque = false;
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

                if(abs($qt_clinica) > 0.001) {
                    $tem_estoque = true;
                }
            }

            $array['quantidade'] = $quantidade;
            if($tem_estoque){
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
            $tem_estoque = false;
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

                if(abs($qt_clinica) > 0.001) {
                    $tem_estoque = true;
                }
            }

            $array['quantidade'] = $quantidade;
            if($tem_estoque){
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
                $medicamento_atual = Medicamento::where('id', $array['id'])->first();

                $codigos = Estoque::select('codigo_barras')
                ->where('medicamento_id', $medicamento_atual->id)
                ->distinct()
                ->get();

                foreach($codigos as $linha){
                    $estoque = Estoque::where('medicamento_id', $medicamento_atual->id)->where('codigo_barras',$linha->codigo_barras)->first();
                    $quantidade_lote = 0;
                    $array_lote = [
                        'lote' => $estoque->lote,
                        'codigo_barras' => $estoque->codigo_barras,
                        'data_vencimento' => dataDbForm($estoque->dt_vencimento),
                        'negrito' => false,
                        'medicamento' => $medicamento_atual->nome,
                        'unidade' => $medicamento_atual->unidade,
                        'estoque_minimo' => $medicamento_atual->estoque_minimo,
                    ];
                    $tem_estoque_lote = false;
                    //vamos buscar as entradas e saidas de cada clinica
                    foreach($clinicas as $clinica){
                        $entrada = Estoque::where('clinica_id', $clinica->id)
                        ->where('medicamento_id', $medicamento_atual->id)
                        ->where('codigo_barras',$linha->codigo_barras)
                        ->where('tipo','Entrada')
                        ->sum('quantidade');

                        $saida = Estoque::where('clinica_id', $clinica->id)
                        ->where('medicamento_id', $medicamento_atual->id)
                        ->where('codigo_barras',$linha->codigo_barras)
                        ->where('tipo','Saida')
                        ->sum('quantidade');

                        $qt_clinica = $entrada - $saida;
                        $array_lote[$clinica->nome] = $qt_clinica;
                        $quantidade_lote += $qt_clinica;

                        if(abs($qt_clinica) > 0.001) {
                            $tem_estoque_lote = true;
                        }
                    }

                    $array_lote['quantidade'] = $quantidade_lote;
                    if($tem_estoque_lote){
                        $array_view[] = $array_lote;
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Estoque');
        
        $cabecalho = ['Medicamento', 'Unidade', 'Estoque Minimo', 'QT Total'];
        foreach($clinicas as $clinica){
            $cabecalho[] = $clinica->nome;
        }
        $cabecalho[] = 'Lote';
        $cabecalho[] = 'C.Barras';
        $cabecalho[] = 'Vencimento';

        $sheet->fromArray($cabecalho, null, 'A2');

        $linhaTotal = 3;
        foreach($array_view as $array){
            $row = [
                $array['medicamento'],
                $array['unidade'],
                $array['estoque_minimo'],
                $array['quantidade']
            ];
            foreach($clinicas as $clinica){
                $row[] = $array[$clinica->nome];
            }
            if(!$array['negrito']){
                $row[] = $array['lote'];
                $row[] = $array['codigo_barras'];
                $row[] = $array['data_vencimento'];
            }
            
            $sheet->fromArray($row, null, 'A' . $linhaTotal);
            
            if($array['negrito']){
                $sheet->getStyle('A' . $linhaTotal . ':Z' . $linhaTotal)->getFont()->setBold(true);
            }
            $linhaTotal++;
        }

        $linhaTotal += 2;
        $sheet->setCellValue('A' . $linhaTotal, 'Estoques Abertos');
        $linhaTotal++;
        
        $cabecalho_abertos = ['Clinica', 'Medicamento', 'Abertura', 'Usuário', 'Lote', 'C.Barras', 'Frasco(mg)', 'Restante'];
        $sheet->fromArray($cabecalho_abertos, null, 'A' . $linhaTotal);
        $linhaTotal++;

        foreach($array_abertos as $array){
            $row = [
                $array['clinica'],
                $array['medicamento'],
                $array['abertura'],
                $array['usuario'],
                $array['lote'],
                $array['codigo_barras'],
                $array['frasco'],
                $array['restante']
            ];
            $sheet->fromArray($row, null, 'A' . $linhaTotal);
            $linhaTotal++;
        }

        $arq = "Estoque_".date('YmdHis');
        $path = public_path('rel_estoque/'.$arq.'.xlsx');
        if(!is_dir(public_path('rel_estoque'))){
            mkdir(public_path('rel_estoque'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }
}
