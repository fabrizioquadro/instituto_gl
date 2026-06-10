<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transferencia;
use App\Models\Clinica;
use App\Models\Medicamento;
use App\Models\Estoque;

class TransferenciaSistemaController extends Controller
{
    public function index(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $transferencias = Transferencia::where('clinica_id', $user->clinica_id)
        ->orWhere('clinica_destino_id', $user->clinica_id)
        ->get();
        return view('sistema/transferencias/index', compact('transferencias','user'));
    }

    public function adicionar(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $clinicas = Clinica::where('id','<>',$user->clinica_id)->orderBy('nome')->get();
        $medicamentos = Medicamento::all()->sortBy('nome');
        return view('sistema/transferencias/adicionar', compact('clinicas','medicamentos'));
    }

    public function insert(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }
            $dados = [
                'clinica_id' => $user->clinica_id,
                'clinica_destino_id' => $request->clinica_destino_id,
                'motivo' => $request->motivo,
                'data' => $request->data,
                'valor' => '0.00',
                'user_id' => $user->id,
            ];
            $transferencia = Transferencia::create($dados);

            //vamos inserir os medicamentos baixados
            $total_transferencia = 0;
            $estoque_ids = [];
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

                    $estoque = Estoque::where('medicamento_id', $medicamento->id)
                    ->where('lote', $lote)
                    ->first();

                    $total = round($quantidade * $medicamento->ultimo_valor_pg, 2);
                    $total_transferencia += $total;
                    //vamos cadastrar a transferencia de saida da clinica origem
                    $dados = [
                        'clinica_id' => $transferencia->clinica_id,
                        'transferencia_id' => $transferencia->id,
                        'medicamento_id' => $medicamento->id,
                        'origem' => 'Transferencia',
                        'tipo' => 'Saida',
                        'quantidade' => $quantidade,
                        'valor' => $medicamento->ultimo_valor_pg,
                        'total' => $total,
                        'lote' => $lote,
                        'codigo_barras' => $codigo_barras,
                        'dt_vencimento' => $estoque->dt_vencimento,
                    ];
                    Estoque::create($dados);

                    //vamos cadastrar a transferencia de entrada na clinica destino
                    $dados = [
                        'clinica_id' => $transferencia->clinica_destino_id,
                        'transferencia_id' => $transferencia->id,
                        'medicamento_id' => $medicamento->id,
                        'origem' => 'Transferencia',
                        'tipo' => 'Entrada',
                        'quantidade' => $quantidade,
                        'valor' => $medicamento->ultimo_valor_pg,
                        'total' => $total,
                        'lote' => $lote,
                        'codigo_barras' => $codigo_barras,
                        'dt_vencimento' => $estoque->dt_vencimento,
                    ];
                    $estoque_entrada = Estoque::create($dados);
                    $estoque_ids[] = $estoque_entrada->id;
                }
            }

            $transferencia->valor = $total_transferencia;
            $transferencia->save();

            return redirect()->route('sistema.transferencias')
                ->with('mensagem', 'Transferencia Cadastrada!')
                ->with('imprimir_etiquetas', json_encode($estoque_ids));
        } catch (\Exception $e) {
            return redirect()->route('sistema.transferencias')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function excluir($id){
        $transferencia = Transferencia::where('id', $id)->first();
        return view('sistema/transferencias/excluir', compact('transferencia'));
    }

    public function delete(Request $request){
        try {
            $transferencia = Transferencia::where('id', $request->transferencia_id)->first();
            Estoque::where('transferencia_id', $transferencia->id)->delete();
            $transferencia->delete();

            return redirect()->route('sistema.transferencias')->with('mensagem', 'Transferência Excluída');
        } catch (\Exception $e) {
            return redirect()->route('sistema.transferencias')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function visualizar($id){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $transferencia = Transferencia::where('id', $id)->first();
        return view('sistema/transferencias/visualizar', compact('transferencia','user'));
    }
    public function get_codigos_barras(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $estoques = \App\Models\Estoque::get_codigos_barras_transferencia($_GET['medicamento_id'], $user->clinica_id);
        $html = "<option value=''>Opções</option>";
        foreach($estoques as $estoque){
            $html .= "<option value='".$estoque['codigo_barras']."'>".$estoque['codigo_barras']."</option>";
        }
        $retorno['codigos'] = $html;
        echo json_encode($retorno);
    }

    public function get_lotes_por_codigo_barras(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $estoques = \App\Models\Estoque::get_lotes_por_codigo_barras_transferencia($_GET['medicamento_id'], $_GET['codigo_barras'], $user->clinica_id);
        $html = "<option value=''>Opções</option>";
        foreach($estoques as $estoque){
            $html .= "<option value='".$estoque['lote']."' data-quantidade='".$estoque['estoque']."'>".$estoque['lote']."</option>";
        }
        $retorno['lotes'] = $html;
        echo json_encode($retorno);
    }
}
