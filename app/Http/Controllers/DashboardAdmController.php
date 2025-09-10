<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Estoque;
use App\Models\Clinica;
use App\Models\Medicamento;

class DashboardAdmController extends Controller
{
    public function index(){
        //vamos buscar os estoques com vencimento para os proximos 60 dias
        $data_hoje = date('Y-m-d');
        $data =  date('Y-m-d', strtotime("+60 days",strtotime($data_hoje)));

        $clinicas = Clinica::all()->sortBy('nome');
        $medicamentos = Medicamento::all()->sortBy('nome');

        $array_view = array();
        foreach($medicamentos as $medicamento){
            $lotes = Estoque::select('lote')
            ->where('medicamento_id',$medicamento->id)
            ->where('dt_vencimento','>=',$data_hoje)
            ->where('dt_vencimento','<=',$data)
            ->distinct()
            ->get();

            foreach($lotes as $linha){
                foreach($clinicas as $clinica){
                    $entrada = Estoque::where('clinica_id', $clinica->id)
                    ->where('medicamento_id', $medicamento->id)
                    ->where('lote',$linha->lote)
                    ->where('tipo','Entrada')
                    ->sum('quantidade');

                    $saida = Estoque::where('clinica_id', $clinica->id)
                    ->where('medicamento_id', $medicamento->id)
                    ->where('lote',$linha->lote)
                    ->where('tipo','Saida')
                    ->sum('quantidade');

                    $estoque = $entrada - $saida;
                    if($estoque > 0){
                        $var = Estoque::where('medicamento_id', $medicamento->id)
                        ->where('lote', $linha->lote)
                        ->first();
                        $array = [
                            'clinica' => $clinica->nome,
                            'medicamento' => $medicamento->nome,
                            'lote' => $linha->lote,
                            'codigo_barras' => $var->codigo_barras,
                            'vencimento' => dataDbForm($var->dt_vencimento),
                            'quantidade' => $estoque,
                        ];
                        $array_view[] = $array;
                    }
                }
            }
        }

        return view('adm/dashboard/index', compact('array_view'));
    }

    public function perfil(){
        $adm = session()->get('administrador');
        return view('adm/dashboard/perfil', compact('adm'));
    }

    public function atualizar_foto(Request $request){
        $adm = session()->get('administrador');
        if($request->hasFile('imagem') && $request->file('imagem')->isValid()){
            $imagem = $request->imagem;
            $extensao = $imagem->extension();

            $nm_imagem = $adm->id.".".$extensao;
            $request->imagem->move(public_path('img/administradores'), $nm_imagem);

            $adm->imagem = $nm_imagem;
            $adm->save();

        }
        return redirect()->route('adm.perfil')->with('mensagem', 'Foto Atualizado!');
    }

    public function resetar_foto(){
        $adm = session()->get('administrador');
        $adm->imagem = null;
        $adm->save();
        return redirect()->route('adm.perfil')->with('mensagem', 'Foto Atualizado!');
    }

    public function update(Request $request){
        $adm = session()->get('administrador');
        $adm->nome = $request->nome;
        $adm->email = $request->email;
        $adm->save();
        return redirect()->route('adm.perfil')->with('mensagem', 'Perfil Atualizado!');
    }

    public function alterar_senha(){
        $adm = session()->get('administrador');
        return view('adm/dashboard/alterar_senha', compact('adm'));
    }

    public function alterar_senha_update(Request $request){
        $adm = session()->get('administrador');
        $adm->password = bcrypt($request->password);
        $adm->save();
        return redirect()->route('adm.perfil')->with('mensagem', 'Senha Alterada!');
    }

    public function alterar_clinica_user(){
        $user = new User();
        $user->id = '0';
        $user->nome = "Adm";
        $user->tipo = "Secretária";
        $user->clinica_id = $_GET['clinica_id'];
        session()->put('user', $user);

        $retorno['controle'] = 'true';
        echo json_encode($retorno);
    }

    public function alterar_tipo_user(){
        $user_old = session()->get('user');

        $user = new User();
        $user->id = '0';
        $user->nome = "Adm";
        $user->tipo = $_GET['tipo'];
        $user->clinica_id = $user_old->clinica_id;

        if($_GET['tipo'] == "Enfermagem"){
            $user = User::where('tipo', 'Enfermagem')
            ->where('clinica_id', $user->clinica_id)
            ->first();
        }

        session()->put('user', $user);

        $retorno['controle'] = 'true';
        echo json_encode($retorno);
    }

    public function alterar_enfermeira(){
        $user = User::where('id', $_GET['user_id'])->first();

        session()->put('user', $user);

        $retorno['controle'] = 'true';
        echo json_encode($retorno);
    }


}
