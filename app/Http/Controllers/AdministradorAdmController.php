<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdministradorAdmController extends Controller
{
    public function index(){
        $adms = User::where('tipo', 'Administrador')->where('st_usuario', 'Ativo')->get();
        return view('adm/administradores/index', compact('adms'));
    }

    public function adicionar(){
        return view('adm/administradores/adicionar');
    }

    public function insert(Request $request){
        try {
            $dados = $request->only('nome','email','st_usuario');
            $dados['password'] = bcrypt($request->password);
            $dados['tipo'] = 'Administrador';
            $dados['clinica_id'] = 8; // clínica padrão dos administradores
            $dados['dashboard_sec'] = 'Sim';
            $dados['dashboard_enf'] = 'Sim';
            $dados['controle_medicamentos'] = 'Sim';
            $dados['pacientes'] = 'Sim';
            $dados['procedimentos'] = 'Sim';
            $dados['financeiro'] = 'Sim';
            $adm = User::create($dados);

            if($request->hasFile('imagem') && $request->file('imagem')->isValid()){
                $imagem = $request->imagem;
                $extensao = $imagem->extension();

                $nm_imagem = $adm->id.".".$extensao;
                $request->imagem->move(public_path('img/administradores'), $nm_imagem);

                $adm->imagem = $nm_imagem;
                $adm->save();
            }

            return redirect()->route('adm.administradores')->with('mensagem', 'Administrador Salvo!');
        }catch(\Exception $e){
            return redirect()->route('adm.administradores')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function editar($id){
        $adm = User::where('id', $id)->where('tipo', 'Administrador')->first();
        return view('adm/administradores/editar', compact('adm'));
    }

    public function update(Request $request){
        try {
            $dados = $request->only('nome','email','st_usuario');
            User::where('id', $request->administrador_id)->where('tipo', 'Administrador')->update($dados);
            $adm = User::where('id', $request->administrador_id)->where('tipo', 'Administrador')->first();

            if($request->hasFile('imagem') && $request->file('imagem')->isValid()){
                $imagem = $request->imagem;
                $extensao = $imagem->extension();

                $nm_imagem = $adm->id.".".$extensao;
                $request->imagem->move(public_path('img/administradores'), $nm_imagem);

                $adm->imagem = $nm_imagem;
                $adm->save();
            }

            return redirect()->route('adm.administradores')->with('mensagem', 'Administrador Salvo!');
        }catch(\Exception $e){
            return redirect()->route('adm.administradores')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function excluir($id){
        $adm = User::where('id', $id)->where('tipo', 'Administrador')->first();
        return view('adm/administradores/excluir', compact('adm'));
    }

    public function delete(Request $request){
        try {
            User::where('id', $request->administrador_id)->where('tipo', 'Administrador')->delete();
            return redirect()->route('adm.administradores')->with('mensagem', 'Administrador Excluído!');
        }catch(\Exception $e){
            return redirect()->route('adm.administradores')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function alterar_senha($id){
        $adm = User::where('id', $id)->where('tipo', 'Administrador')->first();
        return view('adm/administradores/alterar_senha', compact('adm'));
    }

    public function alterar_senha_update(Request $request){
        try {
            $adm = User::where('id', $request->administrador_id)->where('tipo', 'Administrador')->first();
            $adm->password = bcrypt($request->password);
            $adm->save();
            return redirect()->route('adm.administradores')->with('mensagem', 'Senha Alterada!');
        } catch (\Exception $e) {
            return redirect()->route('adm.administradores')->with('mensagem_erro',$e->getMessage());
        }

    }
}
