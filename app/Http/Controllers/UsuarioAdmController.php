<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Clinica;

class UsuarioAdmController extends Controller
{
    //private $opcoes = ['dashboard_sec','dashboard_enf','controle_medicamentos','pacientes','procedimentos'];
    private $opcoes = ['controle_medicamentos','pacientes','procedimentos','financeiro'];

    public function index(){
        $user = session()->get('user');
        $clinica = Clinica::where('id', $user->clinica_id)->first();
        $users = User::where('clinica_id', $user->clinica_id)->get();
        $opcoes = $this->opcoes;
        return view('adm/usuarios/index', compact('users','clinica','opcoes'));
    }

    public function adicionar(){
        $clinicas = Clinica::all()->sortBy('nome');
        $opcoes = $this->opcoes;
        return view('adm/usuarios/adicionar', compact('clinicas','opcoes'));
    }

    public function insert(Request $request){
        try {
            $user = session()->get('user');
            $dados = $request->only('nome','email','tipo','senha_certificado','coren');
            $dados['password'] = bcrypt($request->password);
            $dados['clinica_id'] = $user->clinica_id;

            foreach($this->opcoes as $opcao){
                $coluna = $request->$opcao;
                $dados[$opcao] = $coluna == "Sim" ? 'Sim' : 'Não';
            }

            $user = User::create($dados);

            if($request->hasFile('imagem') && $request->file('imagem')->isValid()){
                $imagem = $request->imagem;
                $extensao = $imagem->extension();

                $nm_imagem = $user->id.".".$extensao;
                $request->imagem->move(public_path('img/usuarios'), $nm_imagem);

                $user->imagem = $nm_imagem;
                $user->save();
            }

            if($request->hasFile('imagem_carimbo') && $request->file('imagem_carimbo')->isValid()){
                $imagem = $request->imagem_carimbo;
                $nm_imagem = $user->id."_".$imagem->getClientOriginalName();

                $request->imagem_carimbo->move(public_path('img/usuarios/certificados_digitais'), $nm_imagem);

                $user->imagem_carimbo = $nm_imagem;
                $user->save();
            }

            return redirect()->route('adm.usuarios')->with('mensagem', 'Usuário Salvo!');
        }catch(\Exception $e){
            return redirect()->route('adm.usuarios')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function editar($id){
        $clinicas = Clinica::all()->sortBy('nome');
        $user = User::where('id', $id)->first();
        $opcoes = $this->opcoes;
        return view('adm/usuarios/editar', compact('clinicas','user','opcoes'));
    }

    public function update(Request $request){
        try {
            $dados = $request->only('nome','email','tipo','clinica_id','senha_certificado','coren');

            foreach($this->opcoes as $opcao){
                $coluna = $request->$opcao;
                $dados[$opcao] = $coluna == "Sim" ? 'Sim' : 'Não';
            }

            User::where('id', $request->user_id)->update($dados);
            $user = User::where('id', $request->user_id)->first();

            if($request->hasFile('imagem') && $request->file('imagem')->isValid()){
                $imagem = $request->imagem;
                $extensao = $imagem->extension();

                $nm_imagem = $user->id.".".$extensao;
                $request->imagem->move(public_path('img/usuarios'), $nm_imagem);

                $user->imagem = $nm_imagem;
                $user->save();
            }

            if($request->hasFile('imagem_carimbo') && $request->file('imagem_carimbo')->isValid()){
                $imagem = $request->imagem_carimbo;
                $nm_imagem = $user->id."_".$imagem->getClientOriginalName();

                $request->imagem_carimbo->move(public_path('img/usuarios/certificados_digitais'), $nm_imagem);

                $user->imagem_carimbo = $nm_imagem;
                $user->save();
            }

            return redirect()->route('adm.usuarios')->with('mensagem', 'Usuário Salvo!');
        }catch(\Exception $e){
            return redirect()->route('adm.usuarios')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function excluir($id){
        $user = User::where('id', $id)->first();
        return view('adm/usuarios/excluir', compact('user'));
    }

    public function delete(Request $request){
        try {
            User::where('id', $request->user_id)->delete();
            return redirect()->route('adm.usuarios')->with('mensagem', 'Usuário Excluído!');
        }catch(\Exception $e){
            return redirect()->route('adm.usuarios')->with('mensagem_erro',$e->getMessage());
        }
    }

    public function alterar_senha($id){
        $user = User::where('id', $id)->first();
        return view('adm/usuarios/alterar_senha', compact('user'));
    }

    public function alterar_senha_update(Request $request){
        try {
            $user = User::where('id', $request->user_id)->first();
            $user->password = bcrypt($request->password);
            $user->save();
            return redirect()->route('adm.usuarios')->with('mensagem', 'Senha Alterada!');
        } catch (\Exception $e) {
            return redirect()->route('adm.usuarios')->with('mensagem_erro',$e->getMessage());
        }

    }
}
