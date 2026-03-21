<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Combo;
use App\Models\ComboMedicamento;
use App\Models\Medicamento;

class ComboController extends Controller
{
    public function index(){
        $combos = Combo::all();
        return view('adm/combos/index', compact('combos'));
    }

    public function adicionar(){
        $medicamentos = Medicamento::all()->sortBy('nome');
        return view('adm/combos/adicionar', compact('medicamentos'));
    }

    public function editar($id){
        $medicamentos = Medicamento::all()->sortBy('nome');
        $combo = Combo::where('id', $id)->first();
        return view('adm/combos/editar', compact('medicamentos','combo'));
    }

    public function excluir($id){
        $combo = Combo::where('id', $id)->first();
        return view('adm/combos/excluir', compact('combo'));
    }

    public function insert(Request $request){
        try {
            $dados = $request->only('nome');
            $combo = Combo::create($dados);

            for($i=1 ; $i<=$request->contador ; $i++){
                $var = "medicamento_id_".$i;
                $medicamento_id = $request->$var;

                $var = "quantidade_".$i;
                $quantidade = $request->$var;

                $var = "valor_".$i;
                $valor = $request->$var;

                if($medicamento_id != "" && $quantidade != "" && $valor != ""){
                    $dados = [
                        'combo_id' => $combo->id,
                        'medicamento_id' => $medicamento_id,
                        'quantidade' => $quantidade,
                        'valor_unitario' => valorFormDb($valor),
                    ];
                    ComboMedicamento::create($dados);
                }
            }

            return redirect()->route('adm.combos')->with('mensagem', 'Combo Cadastrado!');
        } catch (\Exception $e) {
            return redirect()->route('adm.combos')->with('mensagem_erro', $e->getMessage());
        }

    }

    public function update(Request $request){
        try {
            $dados = $request->only('nome');
            Combo::where('id', $request->combo_id)->update($dados);
            $combo = Combo::where('id', $request->combo_id)->first();

            for($i=1 ; $i<=$request->contador ; $i++){
                $var = "medicamento_id_".$i;
                $medicamento_id = $request->$var;

                $var = "quantidade_".$i;
                $quantidade = $request->$var;

                $var = "valor_".$i;
                $valor = $request->$var;

                if($medicamento_id != "" && $quantidade != "" && $valor != ""){
                    $dados = [
                        'combo_id' => $combo->id,
                        'medicamento_id' => $medicamento_id,
                        'quantidade' => $quantidade,
                        'valor_unitario' => valorFormDb($valor),
                    ];
                    ComboMedicamento::create($dados);
                }
            }

            return redirect()->route('adm.combos')->with('mensagem', 'Combo Editado!');
        } catch (\Exception $e) {
            return redirect()->route('adm.combos')->with('mensagem_erro', $e->getMessage());
        }

    }

    public function delete(Request $request){
        try {
            $combo = Combo::where('id', $request->combo_id)->first();

            ComboMedicamento::where('combo_id', $combo->id)->delete();
            $combo->delete();
            return redirect()->route('adm.combos')->with('mensagem', 'Combo Excluído!');
        } catch (\Exception $e) {
            return redirect()->route('adm.combos')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function delete_medicamento(){
        ComboMedicamento::where('id', $_GET['combo_medicamento_id'])->delete();
        $retorno['controle'] = 'true';
        echo json_encode($retorno);
    }

    public function buscar_medicamentos(){
        $combo = Combo::where('id', $_GET['combo_id'])->first();
        $medicamentos = array();

        foreach($combo->medicamentos as $med){
            $array = [
                'medicamento_id' => $med->medicamento_id,
                'quantidade' => $med->quantidade,
                'valor' => valorDbForm($med->valor_unitario),
                'total' => valorDbForm($med->valor_unitario * $med->quantidade),
            ];
            $medicamentos[] = $array;
        }
        $retorno['medicamentos'] = $medicamentos;
        echo json_encode($retorno);
    }

}
