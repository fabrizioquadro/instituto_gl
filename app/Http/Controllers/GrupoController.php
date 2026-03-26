<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grupo;

class GrupoController extends Controller
{
    public function index(){
        $grupos = Grupo::all();
        return view('adm/grupos/index', compact('grupos'));
    }

    public function adicionar(){
        return view('adm/grupos/adicionar');
    }

    public function insert(Request $request){
        try {
            $dados = $request->except('_token');
            Grupo::create($dados);
            return redirect()->route('adm.grupos')->with('mensagem', 'Grupo Cadastrado');
        } catch (\Exception $e) {
            return redirect()->route('adm.grupos')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function editar($id){
        $grupo = Grupo::where('id', $id)->first();
        return view('adm/grupos/editar', compact('grupo'));
    }

    public function update(Request $request){
        try {
            $dados = $request->except('_token','grupo_id');
            Grupo::where('id', $request->grupo_id)->update($dados);
            return redirect()->route('adm.grupos')->with('mensagem', 'Grupo Editado');
        } catch (\Exception $e) {
            return redirect()->route('adm.grupos')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function excluir($id){
        $grupo = Grupo::where('id', $id)->first();
        return view('adm/grupos/excluir', compact('grupo'));
    }

    public function delete(Request $request){
        try {
            Grupo::where('id', $request->grupo_id)->delete();
            return redirect()->route('adm.grupos')->with('mensagem', 'Grupo Excluído');
        } catch (\Exception $e) {
            return redirect()->route('adm.grupos')->with('mensagem_erro', $e->getMessage());
        }
    }
}
