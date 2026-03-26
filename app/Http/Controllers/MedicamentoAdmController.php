<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medicamento;
use App\Models\Grupo;

class MedicamentoAdmController extends Controller
{
    public function index(){
        $medicamentos = Medicamento::all();
        return view('adm/medicamentos/index', compact('medicamentos'));
    }

    public function adicionar(){
        $grupos = Grupo::all()->sortBy('nome');
        return view('adm/medicamentos/adicionar', compact('grupos'));
    }

    public function insert(Request $request){
        try {
            $dados = $request->except('_token','ultimo_valor_pg','vl_venda');
            $dados['ultimo_valor_pg'] = valorFormDb($request->ultimo_valor_pg);
            $dados['vl_venda'] = valorFormDb($request->vl_venda);
            Medicamento::create($dados);
            return redirect()->route('adm.medicamentos')->with('mensagem','Medicamento Cadastrado');
        } catch (\Exception $e) {
            return redirect()->route('adm.medicamentos')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function editar($id){
        $medicamento = Medicamento::where('id', $id)->first();
        $grupos = Grupo::all()->sortBy('nome');
        return view('adm/medicamentos/editar', compact('medicamento','grupos'));
    }

    public function update(Request $request){
        try {
            $dados = $request->except('_token','ultimo_valor_pg','vl_venda','medicamento_id');
            $dados['ultimo_valor_pg'] = valorFormDb($request->ultimo_valor_pg);
            $dados['vl_venda'] = valorFormDb($request->vl_venda);
            Medicamento::where('id', $request->medicamento_id)->update($dados);
            return redirect()->route('adm.medicamentos')->with('mensagem','Medicamento Editado');
        } catch (\Exception $e) {
            return redirect()->route('adm.medicamentos')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function excluir($id){
        $medicamento = Medicamento::where('id', $id)->first();
        return view('adm/medicamentos/excluir', compact('medicamento'));
    }

    public function delete(Request $request){
        try {
            Medicamento::where('id', $request->medicamento_id)->delete();
            return redirect()->route('adm.medicamentos')->with('mensagem','Medicamento Excluído');
        } catch (\Exception $e) {
            return redirect()->route('adm.medicamentos')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function buscar(){
        $medicamento = Medicamento::where('id', $_GET['medicamento_id'])->first();
        $retorno['unidade'] = $medicamento->unidade;

        echo json_encode($retorno);
    }
}
