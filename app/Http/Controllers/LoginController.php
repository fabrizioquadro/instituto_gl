<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Administrador;
use App\Models\User;
use App\Models\Clinica;
use App\Models\Estoque;
use App\Models\Paciente;
use App\Models\Procedimento;

class LoginController extends Controller
{
    public function index(){
        return view('login/index');
    }

    public function login(Request $request){
        $adm = Administrador::where('email', $request->email)->first();
        if($adm){
            if(Hash::check($request->password, $adm->password)){
                session()->put('administrador', $adm);
                session()->put('layout', 'admin');
                $user = new User();
                $user->id = '0';
                $user->nome = "Adm";
                $user->tipo = "Secretária";
                $user->clinica_id = Clinica::all()->first()->id;
                session()->put('user', $user);

                return redirect()->route('adm.dashboard');
            }
            else{
                return redirect()->back()->with('erro', "Senha Inválido");
            }
        }
        else{
            $dados = $request->except('_token');
            if(Auth::attempt($dados)){
                $request->session()->regenerate();
                $user = auth()->user();
                session()->put('layout', 'sistema');
                return redirect()->route('sistema.dashboard');
            }
            else{
                return redirect()->back()->with('erro', "Email ou senha inválidos");
            }
        }
    }

    public function esqueceu_senha(){
        return view('login/esqueceu_senha');
    }

    public function recuperar_senha(Request $request){
        $adm = Administrador::where('email', $request->email)->first();
        if($adm){
            $novaSenha = createPassword(8, true, true, true, false);

            $adm->password = bcrypt($novaSenha);
            $adm->save();

            $mensagem = "
            <h4>Nova Senha de Acesso ao Instituto GL - Sistema Online</h4>
            <p>
                Foi alterado por sua solicitação a senha de acesso ao sistema.
            </p>
            <p>
                Sua nova senha é: $novaSenha
            </p>
            ";

            enviarMail($adm->email, 'Nova Senha de Acesso', $mensagem);

            return redirect()->route('index')->with('sucesso','Sua nova senha foi enviado para o seu email.');
        }
        else{
            $user = User::where('email', $request->email)->first();
            if($user){
                $novaSenha = createPassword(8, true, true, true, false);

                $user->password = bcrypt($novaSenha);
                $user->save();

                $mensagem = "
                <h4>Nova Senha de Acesso ao Instituto GL - Sistema Online</h4>
                <p>
                    Foi alterado por sua solicitação a senha de acesso ao sistema.
                </p>
                <p>
                    Sua nova senha é: $novaSenha
                </p>
                ";

                enviarMail($user->email, 'Nova Senha de Acesso', $mensagem);

                return redirect()->route('index')->with('sucesso','Sua nova senha foi enviado para o seu email.');
            }
            else{
                return redirect()->back()->with('erro', "Email inválido");
            }
        }
    }

    public function logout(Request $request){
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('index');
    }

    public function teste(){
        $api = new ApiFlegowController();
        $procedimento = Procedimento::where('id', '1512')->first();
        $api->register_aplicacao($procedimento);
    }

}
