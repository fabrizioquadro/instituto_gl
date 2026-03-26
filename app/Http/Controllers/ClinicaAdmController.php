<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clinica;

class ClinicaAdmController extends Controller
{
    public function index(){
        $clinicas = Clinica::all();
        return view('adm/clinicas/index', compact('clinicas'));
    }

    public function adicionar(){
        $api = api();
        $unidades = $api->get_unidades();
        return view('adm/clinicas/adicionar', compact('unidades'));
    }

    public function insert(Request $request){
        try {
            $dados = $request->except('_token');
            Clinica::create($dados);
            return redirect()->route('adm.clinicas')->with('mensagem','Clinica Cadastrada!');
        } catch (\Exception $e) {
            return redirect()->route('adm.clinicas')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function editar($id){
        $clinica = Clinica::where('id', $id)->first();
        $api = api();
        $unidades = $api->get_unidades();
        return view('adm/clinicas/editar', compact('unidades','clinica'));
    }

    public function update(Request $request){
        try {
            $dados = $request->except('_token', 'clinica_id');
            Clinica::where('id', $request->clinica_id)->update($dados);
            return redirect()->route('adm.clinicas')->with('mensagem','Clinica Editada!');
        } catch (\Exception $e) {
            return redirect()->route('adm.clinicas')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function excluir($id){
        $clinica = Clinica::where('id', $id)->first();
        return view('adm/clinicas/excluir', compact('clinica'));
    }

    public function delete(Request $request){
        try {
            Clinica::where('id', $request->clinica_id)->delete();
            return redirect()->route('adm.clinicas')->with('mensagem','Clinica Excluído!');
        } catch (\Exception $e) {
            return redirect()->route('adm.clinicas')->with('mensagem_erro',$e->getMessage());
        }
    }
}
