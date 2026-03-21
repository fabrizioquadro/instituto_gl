<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Baixa;
use App\Models\BaixaAberto;
use App\Models\Estoque;
use App\Models\Medicamento;
use App\Models\EstoqueAberto;

class BaixaSistemaController extends Controller
{
    public function index(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $baixas = Baixa::where('clinica_id', $user->clinica_id)->get();
        return view('sistema/baixas/index', compact('baixas'));
    }

    public function index_abertos(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $baixas = BaixaAberto::where('clinica_id', $user->clinica_id)->get();
        return view('sistema/baixas/index_abertos', compact('baixas'));
    }

    public function adicionar(){
        $medicamentos = Medicamento::all()->sortBy('nome');
        return view('sistema/baixas/adicionar', compact('medicamentos'));
    }

    public function adicionar_abertos(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }

        $abertos = EstoqueAberto::where('clinica_id',$user->clinica_id)
        ->where('situacao','Aberto')
        ->get();
        return view('sistema/baixas/adicionar_abertos', compact('abertos'));
    }

    public function get_lotes_medicamento(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $estoques = Estoque::get_lotes_medicamento($_GET['medicamento_id'],$user->clinica_id);
        $html = "<option value=''>Opções</option>";
        foreach($estoques as $estoque){
            $html .= "<option value='".$estoque['lote']."' data-quantidade='".$estoque['estoque']."'>".$estoque['lote']."</option>";
        }
        $retorno['lotes'] = $html;
        echo json_encode($retorno);
    }

    public function insert_abertos(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            $estoque = EstoqueAberto::where('id', $request->estoque_aberto_id)->first();

            $dados = [
                'user_id' => $user->id,
                'clinica_id' => $estoque->clinica_id,
                'estoque_aberto_id' => $estoque->id,
                'quantidade' => $request->quantidade,
                'motivo' => $request->motivo,
            ];

            BaixaAberto::create($dados);
            if($request->quantidade >= $estoque->qt_restante){
                $estoque->qt_restante = 0;
                $estoque->qt_utilizado = $estoque->qt_inical;
                $estoque->situacao  = 'Finalizado';
            }
            else{
                $estoque->qt_utilizado += $request->quantidade;
                $estoque->qt_restante -= $request->quantidade;
            }
            $estoque->save();

            return redirect()->route('sistema.baixas_abertos')->with('mensagem', 'Baixa Cadastrada!');
        } catch (\Exception $e) {
            return redirect()->route('sistema.baixas_abertos')->with('mensagem_erro', $e->getMessage());
        }

    }

    public function insert(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }
            $dados = [
                'clinica_id' => $user->clinica_id,
                'motivo' => $request->motivo,
                'data' => $request->data,
                'valor' => '0.00',
            ];
            $baixa = Baixa::create($dados);

            //vamos inserir os medicamentos baixados
            $total_baixa = 0;
            for($i=1 ; $i<=$request->contador_medicamentos ; $i++){
                $var = 'medicamento_id_'.$i;
                $medicamento_id = $request->$var;
                if($medicamento_id){
                    $var = "lote_".$i;
                    $lote = $request->$var;

                    $var = "quantidade_".$i;
                    $quantidade = $request->$var;

                    $var = "codigo_barras_".$i;
                    $codigo_barras = $request->$var;

                    $medicamento = Medicamento::where('id', $medicamento_id)->first();
                    $total = round($quantidade * $medicamento->ultimo_valor_pg, 2);
                    $total_baixa += $total;
                    //vamos buscar a data de vencimento do lote do medicamento
                    $estoque = Estoque::where('medicamento_id', $medicamento->id)
                    ->where('lote', $lote)
                    ->first();

                    $dados = [
                        'clinica_id' => $baixa->clinica_id,
                        'baixa_id' => $baixa->id,
                        'medicamento_id' => $medicamento->id,
                        'origem' => 'Baixa',
                        'tipo' => 'Saida',
                        'quantidade' => $quantidade,
                        'valor' => $medicamento->ultimo_valor_pg,
                        'total' => $total,
                        'lote' => $lote,
                        'codigo_barras' => $codigo_barras,
                        'dt_vencimento' => $estoque->dt_vencimento,
                    ];
                    Estoque::create($dados);
                }
            }

            $baixa->valor = $total_baixa;
            $baixa->save();

            return redirect()->route('sistema.baixas')->with('mensagem', 'Baixa Cadastrada!');
        } catch (\Exception $e) {
            return redirect()->route('sistema.baixas')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function editar($id){
        //esta opção foi descontinuada
        die();
        $baixa = Baixa::where('id', $id)->first();
        $medicamentos = Medicamento::all()->sortBy('nome');
        $i = 0;
        return view('sistema/baixas/editar', compact('medicamentos','baixa','i'));
    }

    public function excluir($id){
        $baixa = Baixa::where('id', $id)->first();
        return view('sistema/baixas/excluir', compact('baixa'));
    }

    public function excluir_abertos($id){
        $baixa = BaixaAberto::where('id', $id)->first();
        return view('sistema/baixas/excluir_abertos', compact('baixa'));
    }

    public function delete(Request $request){
        try {
            $baixa = Baixa::where('id', $request->baixa_id)->first();
            Estoque::where('baixa_id', $baixa->id)->delete();
            $baixa->delete();

            return redirect()->route('sistema.baixas')->with('mensagem', 'Baixa Excluída');
        } catch (\Exception $e) {
            return redirect()->route('sistema.baixas')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function delete_abertos(Request $request){
        try {
            $baixa = BaixaAberto::where('id', $request->baixa_id)->first();
            $estoque = EstoqueAberto::where('id', $baixa->estoque_aberto_id)->first();

            $estoque->qt_restante += $baixa->quantidade;
            $estoque->qt_utilizado -= $baixa->quantidade;
            if($estoque->qt_restante > 0){
                $estoque->situacao = 'Aberto';
            }
            $estoque->save();
            $baixa->delete();

            return redirect()->route('sistema.baixas_abertos')->with('mensagem', 'Baixa Excluída');
        } catch (\Exception $e) {
            return redirect()->route('sistema.baixas_abertos')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function visualizar($id){
        $baixa = Baixa::where('id', $id)->first();
        return view('sistema/baixas/visualizar', compact('baixa'));
    }



}
