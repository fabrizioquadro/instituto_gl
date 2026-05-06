<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Administrador;

class AdministradorAdmController extends Controller
{
    public function index(){
        $adms = Administrador::where('st_usuario', 'Ativo')->get();
        return view('adm/administradores/index', compact('adms'));
    }

    public function adicionar(){
        return view('adm/administradores/adicionar');
    }

    public function insert(Request $request){
        try {
            $dados = $request->except('_token','password','imagem');
            $dados['password'] = bcrypt($request->password);
            $adm = Administrador::create($dados);

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
        $adm = Administrador::where('id', $id)->first();
        return view('adm/administradores/editar', compact('adm'));
    }

    public function update(Request $request){
        try {
            $dados = $request->except('_token','administrador_id','imagem');
            $adm = Administrador::where('id', $request->administrador_id)->update($dados);
            $adm = Administrador::where('id', $request->administrador_id)->first();

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
        $adm = Administrador::where('id', $id)->first();
        return view('adm/administradores/excluir', compact('adm'));
    }

    public function delete(Request $request){
        try {
            $adm = Administrador::where('id', $request->administrador_id)->delete();
            return redirect()->route('adm.administradores')->with('mensagem', 'Administrador Excluído!');
        }catch(\Exception $e){
            return redirect()->route('adm.administradores')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function alterar_senha($id){
        $adm = Administrador::where('id', $id)->first();
        return view('adm/administradores/alterar_senha', compact('adm'));
    }

    public function alterar_senha_update(Request $request){
        try {
            $adm = Administrador::where('id', $request->administrador_id)->first();
            $adm->password = bcrypt($request->password);
            $adm->save();
            return redirect()->route('adm.administradores')->with('mensagem', 'Senha Alterada!');
        } catch (\Exception $e) {
            return redirect()->route('adm.administradores')->with('mensagem_erro',$e->getMessage());
        }

    }
}
