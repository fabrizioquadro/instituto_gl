<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Estoque;
use App\Models\Clinica;
use App\Models\Medicamento;
use App\Models\Procedimento;
use App\Models\Aplicacao;

class DashboardAdmController extends Controller
{
    public $cores = [
        "#1ABC9C", "#16A085", "#2ECC71", "#27AE60",
        "#3498DB", "#2980B9", "#9B59B6", "#8E44AD",
        "#34495E", "#2C3E50", "#F1C40F", "#F39C12",
        "#E67E22", "#D35400", "#E74C3C", "#C0392B",
        "#ECF0F1", "#BDC3C7", "#95A5A6", "#7F8C8D",
        "#FF6B6B", "#FF9F43", "#F368E0", "#48DBFB",
        "#1DD1A1", "#10AC84", "#5F27CD", "#341F97",
        "#00D2D3", "#54A0FF", "#576574", "#8395A7",
        "#222F3E", "#EE5253", "#FDCB6E", "#6C5CE7",
        "#00B894", "#0984E3", "#FAB1A0", "#636E72"
    ];

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


        //vamos montar a imagem dos faturamentos
        if(isset($_GET['controle'])){
            $controle = $_GET['controle'];
        }
        else{
            $controle = 'Dia';
        }

        $data_inc = date('Y-m-d');

        if($controle == 'Dia'){
            $data_fn = date('Y-m-d', strtotime("+1 days",strtotime($data_inc)));
        }
        elseif($controle == 'Semana'){
            $data_fn = date('Y-m-d', strtotime("+7 days",strtotime($data_inc)));
        }
        elseif($controle == 'Mês'){
            $data_fn = date('Y-m-d', strtotime("+30 days",strtotime($data_inc)));
        }

        //vamos discobrir os faturamentos totais
        $clinicas = Clinica::all();

        $procedimentos = Procedimento::where('data_aplicacao','>=',$data_inc)
        ->where('data_aplicacao','<',$data_fn)->get();
        $array_in = array();
        foreach($procedimentos as $proc){
            $array_in[] = $proc->id;
        }

        //vamos buscar o somatorio total
        $vl_faturamento = Procedimento::whereIn('id', $array_in)
        ->sum('valor');

        $array_clinicas = array();
        $total_clinicas = 0;
        foreach($clinicas as $clinica){
            $valor = Procedimento::whereIn('id', $array_in)
            ->where('clinica_id', $clinica->id)
            ->sum('valor');
            if($valor > 0){
                $array = [
                    'clinica' => $clinica->nome,
                    'valor' => $valor,
                ];
                $array_clinicas[] = $array;
                $total_clinicas += $valor;
            }
        }

        //vamos fazer referente a cada medico
        $medicos = Procedimento::whereIn('id', $array_in)
        ->distinct()->pluck('medico');

        $array_medicos = array();
        $total_medicos = 0;
        foreach($medicos as $medico){
            $valor = Procedimento::whereIn('id', $array_in)
            ->where('medico', $medico)
            ->sum('valor');
            if($valor > 0){
                $array = [
                    'medico' => $medico,
                    'valor' => $valor,
                ];
                $array_medicos[] = $array;
                $total_medicos += $valor;
            }
        }

        //vamos ver referentes aos Medicamentos
        $medicamentos = Aplicacao::whereIn('procedimento_id', $array_in)
        ->distinct()->pluck('medicamento_id');

        $array_medicamentos = array();
        $total_medicamentos = 0;
        foreach($medicamentos as $medicamento_id){
            $valor = Aplicacao::whereIn('procedimento_id', $array_in)
            ->where('medicamento_id', $medicamento_id)
            ->sum('total');

            if($valor > 0){
                $med = Medicamento::where('id', $medicamento_id)->first();
                $array = [
                    'medicamento' => $med->nome,
                    'valor' => $valor,
                ];
                $array_medicamentos[] = $array;
                $total_medicamentos += $valor;
            }
        }

        //vamos montar a parte das clinicas para o javascript
        //clinicas
        $label_clinicas = "";
        $valores_clinicas = "";
        $cores_clinicas = "";
        $c = 0;
        foreach($array_clinicas as $array){
            $label_clinicas .= ",'".$array['clinica']."'";
            $porcentagem = round( $array['valor'] * 100 / $total_clinicas ,2);
            $valores_clinicas .= ",$porcentagem";
            $cores_clinicas .= ",'".$this->cores[$c++]."'";
        }

        $label_clinicas = substr($label_clinicas,1);
        $valores_clinicas = substr($valores_clinicas,1);
        $cores_clinicas = substr($cores_clinicas,1);

        //medicos
        $label_medicos = "";
        $valores_medicos = "";
        $cores_medicos = "";
        $c = 0;
        foreach($array_medicos as $array){
            $label_medicos .= ",'".$array['medico']."'";
            $porcentagem = round( $array['valor'] * 100 / $total_medicos ,2);
            $valores_medicos .= ",$porcentagem";
            $cores_medicos .= ",'".$this->cores[$c++]."'";
        }

        $label_medicos = substr($label_medicos,1);
        $valores_medicos = substr($valores_medicos,1);
        $cores_medicos = substr($cores_medicos,1);

        //medicamentos
        $label_medicamentos = "";
        $valores_medicamentos = "";
        $cores_medicamentos = "";
        $c = 0;
        foreach($array_medicamentos as $array){
            $label_medicamentos .= ",'".$array['medicamento']."'";
            $porcentagem = round( $array['valor'] * 100 / $total_medicamentos ,2);
            $valores_medicamentos .= ",$porcentagem";
            $cores_medicamentos .= ",'".$this->cores[$c++]."'";
        }

        $label_medicamentos = substr($label_medicamentos,1);
        $valores_medicamentos = substr($valores_medicamentos,1);
        $cores_medicamentos = substr($cores_medicamentos,1);

        //consumo de medicamentos (quantidades aplicadas de todo o banco)
        $consumos = Aplicacao::selectRaw('medicamento_id, sum(quantidade) as total_quantidade')
                             ->where('situacao', 'Aplicada')
                             ->groupBy('medicamento_id')
                             ->get();
        $array_consumo = array();
        foreach($consumos as $c){
            if($c->total_quantidade > 0){
                $med = Medicamento::find($c->medicamento_id);
                if($med) {
                    $array = [
                        'medicamento' => $med->nome,
                        'quantidade' => $c->total_quantidade,
                    ];
                    $array_consumo[] = $array;
                }
            }
        }
        
        $label_consumo = "";
        $valores_consumo = "";
        $cores_consumo = "";
        $c = 0;
        foreach($array_consumo as $array){
            $label_consumo .= ",'".$array['medicamento']."'";
            $valores_consumo .= ",".$array['quantidade'];
            $cores_consumo .= ",'".$this->cores[$c++ % count($this->cores)]."'";
        }

        $label_consumo = substr($label_consumo,1) ?: "''";
        $valores_consumo = substr($valores_consumo,1) ?: "0";
        $cores_consumo = substr($cores_consumo,1) ?: "''";

        $administrador = session()->get('administrador');

        return view('adm/dashboard/index', compact('array_view','controle',
        'vl_faturamento','label_clinicas','valores_clinicas','cores_clinicas',
        'label_medicos','valores_medicos','cores_medicos','label_medicamentos',
        'valores_medicamentos','cores_medicamentos','administrador',
        'label_consumo','valores_consumo','cores_consumo'));
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
            ->where('st_usuario', 'Ativo')
            ->first();
        }

        session()->put('user', $user);

        $retorno['controle'] = 'true';
        echo json_encode($retorno);
    }

    public function alterar_enfermeira(){
        $user = User::where('id', $_GET['user_id'])->where('st_usuario', 'Ativo')->first();

        session()->put('user', $user);

        $retorno['controle'] = 'true';
        echo json_encode($retorno);
    }


}
