<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fornecedor;

class FornecedorAdmController extends Controller
{
    public function index(){
        $fornecedores = Fornecedor::all();
        return view('adm/fornecedores/index', compact('fornecedores'));
    }

    public function adicionar(){
        return view('adm/fornecedores/adicionar');
    }

    public function insert(Request $request){
        try {
            $dados = $request->except('_token');
            Fornecedor::create($dados);
            return redirect()->route('adm.fornecedores')->with('mensagem', 'Fornecedor Cadastrado!');
        } catch (\Exception $e) {
            return redirect()->route('adm.fornecedores')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function editar($id){
        $fornecedor = Fornecedor::where('id', $id)->first();
        return view('adm/fornecedores/editar', compact('fornecedor'));
    }

    public function update(Request $request){
        try {
            $dados = $request->except('_token','fornecedor_id');
            Fornecedor::where('id', $request->fornecedor_id)->update($dados);
            return redirect()->route('adm.fornecedores')->with('mensagem', 'Fornecedor Editado!');
        } catch (\Exception $e) {
            return redirect()->route('adm.fornecedores')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function excluir($id){
        $fornecedor = Fornecedor::where('id', $id)->first();
        return view('adm/fornecedores/excluir', compact('fornecedor'));
    }

    public function delete(Request $request){
        try {
            Fornecedor::where('id', $request->fornecedor_id)->delete();
            return redirect()->route('adm.fornecedores')->with('mensagem', 'Fornecedor Excluído!');
        } catch (\Exception $e) {
            return redirect()->route('adm.fornecedores')->with('mensagem_erro', $e->getMessage());
        }
    }
}
