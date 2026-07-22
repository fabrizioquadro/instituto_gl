<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimento;
use App\Models\Estoque;
use App\Models\EstoqueAberto;
use App\Models\Medicamento;
use App\Models\AplicacaoLote;
use App\Models\Clinica;
use App\Models\Paciente;

class DashboardSistemaController extends Controller
{
    public function index(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $dados_pesquisa = array();
        if($user->tipo == "Enfermagem"){
            $procedimentos_aguardando = Procedimento::where('clinica_id_aplicacao', $user->clinica_id)
            ->where('situacao','Fila de Aplicação')
            ->orderBy('updated_at')
            ->get();

            $procedimentos_atendimento = Procedimento::where('clinica_id_aplicacao', $user->clinica_id)
            ->where('situacao','Atendimento')
            ->orderBy('updated_at')
            ->get();

            $procedimentos_aplicadas = Procedimento::where('clinica_id_aplicacao', $user->clinica_id)
            ->where('situacao','Aplicado')
            ->where('data_aplicacao',date('Y-m-d'))
            ->orderBy('updated_at')
            ->get();

            //$procedimentos = Procedimento::where('clinica_id_aplicacao', $user->clinica_id)
            //->where(function ($query) use ($user){
            //    $query->where('situacao','Fila de Aplicação')
            //    ->orWhere(function ($query2) use ($user) {
            //        $query2->where('situacao', 'Atendimento')
            //        ->where('user_id_aplicacao', $user->id);
            //    });
            //})
            //->orderBy('updated_at')
            //->get();

            $array_abertos = array();
            $estoques = EstoqueAberto::where('clinica_id', $user->clinica_id)
            ->where('situacao','Aberto')
            ->get();
            $clinica = Clinica::where('id', $user->clinica_id)->first();
            foreach($estoques as $estoque){
                $array = [
                    'clinica' => $clinica->nome,
                    'medicamento' => $estoque->medicamento ? $estoque->medicamento->nome : 'Medicamento Desconhecido (ID: ' . $estoque->medicamento_id . ')',
                    'abertura' => dataDbForm($estoque->dt_cadastro),
                    'usuario' => $estoque->user->nome,
                    'frasco' => $estoque->qt_inical." (mg)",
                    'restante' => $estoque->qt_restante." (mg)",
                    'lote' => $estoque->lote,
                    'codigo_barras' => $estoque->codigo_barras,
                ];
                $array_abertos[] = $array;
            }

            return view('sistema/dashboard/index_enfermeira', compact('procedimentos_aguardando',
            'procedimentos_atendimento','procedimentos_aplicadas','user','array_abertos'));
        }
        else{
            $paciente_id = null;
            $paciente = null;
            if($_POST){
                $paciente_id = $_POST['paciente_id'] ? $_POST['paciente_id'] : $_POST['paciente_controle'];
                $paciente = Paciente::where('id', $paciente_id)->first();
                $dados_pesquisa['dt_procedimentos'] = $_POST['dt_procedimentos'];
                $dados_pesquisa['st_pagamento'] = $_POST['st_pagamento'];
                $dados_pesquisa['situacao'] = $_POST['situacao'];
                $dados_pesquisa['tipo_atendimento'] = isset($_POST['tipo_atendimento']) ? $_POST['tipo_atendimento'] : '';
                $dados_pesquisa['paciente_id'] = $paciente_id;
                $dados_pesquisa['paciente_controle'] = $paciente ? $paciente->id : '';

                $procedimentos = Procedimento::lista_procedimentos_filtro($dados_pesquisa);
            }
            else{
                $data = date('Y-m-d');

                $dados_pesquisa['dt_procedimentos'] = $data;
                $dados_pesquisa['st_pagamento'] = '';
                $dados_pesquisa['situacao'] = 'Agendado';
                $dados_pesquisa['tipo_atendimento'] = '';
                $dados_pesquisa['paciente_controle'] = '';

                $procedimentos = Procedimento::where('clinica_id', $user->clinica_id)
                ->where('data_aplicacao', $data)
                ->where('situacao', 'Agendado')
                ->where('situacao', 'Aplicação Parcial')
                ->get();
            }

            //vamos buscar os procedimentos que estao atrasados a mais de 7 dias
            $data_hoje = date('Y-m-d');
            $data =  date('Y-m-d', strtotime("-7 days",strtotime($data_hoje)));

            $proc_atrasados = Procedimento::where('clinica_id', $user->clinica_id)
            ->where('data_aplicacao', '<=', $data)
            ->where('situacao', 'Agendado')
            ->get();

            $vencimentos = Estoque::get_medicamentos_vencimento($user->clinica_id);

            return view('sistema/dashboard/index', compact('procedimentos','paciente_id','paciente','dados_pesquisa','proc_atrasados','vencimentos'));
        }
    }

    public function perfil(){
        $user = auth()->user();
        $clinicas = Clinica::all()->sortBy('nome');
        return view('sistema/dashboard/perfil', compact('user','clinicas'));
    }

    public function atualizar_foto(Request $request){
        $user = auth()->user();
        if($request->hasFile('imagem') && $request->file('imagem')->isValid()){
            $imagem = $request->imagem;
            $extensao = $imagem->extension();

            $nm_imagem = $user->id.".".$extensao;
            $request->imagem->move(public_path('img/usuarios'), $nm_imagem);

            $user->imagem = $nm_imagem;
            $user->save();

        }
        return redirect()->route('sistema.perfil')->with('mensagem', 'Foto Atualizado!');
    }

    public function resetar_foto(){
        $user = auth()->user();
        $user->imagem = null;
        $user->save();
        return redirect()->route('sistema.perfil')->with('mensagem', 'Foto Atualizado!');
    }

    public function update(Request $request){
        $user = auth()->user();
        $user->nome = $request->nome;
        $user->email = $request->email;
        $user->clinica_id = $request->clinica_id;
        $user->save();
        return redirect()->route('sistema.perfil')->with('mensagem', 'Perfil Atualizado!');
    }

    public function alterar_senha(){
        $user = auth()->user();
        return view('sistema/dashboard/alterar_senha', compact('user'));
    }

    public function alterar_senha_update(Request $request){
        $user = auth()->user();
        $user->password = bcrypt($request->password);
        $user->save();
        return redirect()->route('sistema.perfil')->with('mensagem', 'Senha Alterada!');
    }

    public function enfermagem_acessar_procedimento($id){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $procedimento = Procedimento::where('id', $id)->first();

        if($procedimento->st_pagamento != "Sim" && !$procedimento->autorizador_sem_pagamento && $procedimento->valor > 0){
            echo "Esse procedimento não esta pago para fazer a aplicação";
            die();
        }

        if($procedimento->situacao == "Aplicado" || $procedimento->situacao == "Finalizado"){
            return redirect()->route('sistema.dashboard.enfermagem_visualizar_procedimento', $id);
        }

        if($procedimento->situacao != "Fila de Aplicação" && $procedimento->situacao != "Aplicação Parcial" && $procedimento->user_id_aplicacao != $user->id){
            return redirect()->route('sistema.dashboard')->with('mensagem_erro', 'Este Paciente já esta sendo atendido!');
        }
        $procedimento->situacao = "Atendimento";
        $procedimento->dt_hr_atendimento = date('Y-m-d H:i:s');
        $procedimento->user_id_aplicacao = $user->id;
        $procedimento->save();
        $procedimentos_vinculados = Procedimento::where('codigo', $procedimento->codigo)
        ->where('id','<>', $procedimento->id)
        ->orderBy('nr_procedimento')
        ->get();
        $user = auth()->user();
        $controle = 'sistema';
        if(!$user){
            $user = session()->get('user');
            $controle = 'admin';
        }

        $api = api();
        $nascimento = $api->get_nascimento_paciente($procedimento->paciente->paciente_id_feegow);

        $array_abertos = array();
        $estoques = EstoqueAberto::where('clinica_id', $user->clinica_id)
        ->where('situacao','Aberto')
        ->get();
        $clinica = Clinica::where('id', $user->clinica_id)->first();
        foreach($estoques as $estoque){
            $array = [
                'clinica' => $clinica->nome,
                'medicamento' => $estoque->medicamento ? $estoque->medicamento->nome : 'Medicamento Desconhecido (ID: ' . $estoque->medicamento_id . ')',
                'abertura' => dataDbForm($estoque->dt_cadastro),
                'usuario' => $estoque->user->nome,
                'frasco' => $estoque->qt_inical." (mg)",
                'restante' => $estoque->qt_restante." (mg)",
                'lote' => $estoque->lote,
                'codigo_barras' => $estoque->codigo_barras,
            ];
            $array_abertos[] = $array;
        }

        if(isset($_GET['controle'])){
            return view('sistema/dashboard/enfermeira_acessar_procedimento_new', compact('procedimento','user','controle','procedimentos_vinculados','nascimento'));
        }
        else{
            return view('sistema/dashboard/enfermeira_acessar_procedimento', compact('procedimento','user','controle','procedimentos_vinculados','nascimento','array_abertos'));
        }
    }

    public function enfermagem_visualizar_procedimento($id){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $procedimento = Procedimento::where('id', $id)->first();

        $procedimentos_vinculados = Procedimento::where('codigo', $procedimento->codigo)
        ->where('id','<>', $procedimento->id)
        ->orderBy('nr_procedimento')
        ->get();
        $controle = 'sistema';
        if(!$user){
            $user = session()->get('user');
            $controle = 'admin';
        }

        $api = api();
        $nascimento = $api->get_nascimento_paciente($procedimento->paciente->paciente_id_feegow);

        $array_abertos = array();
        $estoques = EstoqueAberto::where('clinica_id', $user->clinica_id)
        ->where('situacao','Aberto')
        ->get();
        $clinica = Clinica::where('id', $user->clinica_id)->first();
        foreach($estoques as $estoque){
            $array = [
                'clinica' => $clinica->nome,
                'medicamento' => $estoque->medicamento ? $estoque->medicamento->nome : 'Medicamento Desconhecido (ID: ' . $estoque->medicamento_id . ')',
                'abertura' => dataDbForm($estoque->dt_cadastro),
                'usuario' => $estoque->user->nome,
                'frasco' => $estoque->qt_inical." (mg)",
                'restante' => $estoque->qt_restante." (mg)",
                'lote' => $estoque->lote,
                'codigo_barras' => $estoque->codigo_barras,
            ];
            $array_abertos[] = $array;
        }

        $visualizar = true;

        if(isset($_GET['controle'])){
            return view('sistema/dashboard/enfermeira_acessar_procedimento_new', compact('procedimento','user','controle','procedimentos_vinculados','nascimento','visualizar'));
        }
        else{
            return view('sistema/dashboard/enfermeira_acessar_procedimento', compact('procedimento','user','controle','procedimentos_vinculados','nascimento','array_abertos','visualizar'));
        }
    }

    public function busca_lote_por_codigo(){
        $estoque = Estoque::where('codigo_barras', $_GET['codigo'])
        ->where('clinica_id', $_GET['clinica_id'])
        ->where('medicamento_id', $_GET['medicamento_id'])
        ->first();

        if($estoque){
            // Verificar se o lote está vencido
            if($estoque->dt_vencimento && $estoque->dt_vencimento < date('Y-m-d')){
                $retorno['controle'] = 'vencido';
                $retorno['lote'] = $estoque->lote;
                $retorno['mensagem'] = 'Este medicamento está VENCIDO desde ' . dataDbForm($estoque->dt_vencimento) . '. Não é possível aplicar.';
                echo json_encode($retorno);
                return;
            }

            //vamos verificar se este medicamento possui saldo
            $saldo = Estoque::get_saldo_med_cb_clinica($_GET['codigo'], $_GET['clinica_id']);
            if($_GET['quantidade'] > $saldo){
                $retorno['controle'] = 'insuficiente';
                $retorno['lote'] = '';
            }
            else{
                $retorno['lote'] = $estoque->lote;
                $retorno['controle'] = 'true';
            }
        }
        else{
            $retorno['lote'] = '';
            $retorno['controle'] = 'false';
        }
        echo json_encode($retorno);
    }

    public function busca_lote_por_codigo_frasco(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }

        $medicamento = Medicamento::where('id', $_GET['medicamento_id'])->first();
        if($medicamento->grupo_id){
            $medicamentos = Medicamento::where('grupo_id', $medicamento->grupo_id)->get();
            $in = array();
            foreach($medicamentos as $med){
                $in[] = $med->id;
            }

            $estoque = EstoqueAberto::where('clinica_id', $user->clinica_id)
            ->whereIn('medicamento_id', $in)
            ->where('codigo_barras', $_GET['codigo'])
            ->where('situacao','Aberto')
            ->first();
        }
        else{
            $estoque = EstoqueAberto::where('clinica_id', $user->clinica_id)
            ->where('medicamento_id', $_GET['medicamento_id'])
            ->where('codigo_barras', $_GET['codigo'])
            ->where('situacao','Aberto')
            ->first();
        }

        if($estoque){
            // Verificar se o lote está vencido (buscar dt_vencimento no Estoque)
            $estoque_original = Estoque::where('codigo_barras', $_GET['codigo'])
            ->where('medicamento_id', $_GET['medicamento_id'])
            ->where('clinica_id', $user->clinica_id)
            ->where('lote', $estoque->lote)
            ->first();

            if($estoque_original && $estoque_original->dt_vencimento && $estoque_original->dt_vencimento < date('Y-m-d')){
                $retorno['controle'] = 'vencido';
                $retorno['lote'] = $estoque->lote;
                $retorno['mensagem'] = 'Este medicamento está VENCIDO desde ' . dataDbForm($estoque_original->dt_vencimento) . '. Não é possível aplicar.';
                echo json_encode($retorno);
                return;
            }

            if($estoque->qt_restante < $_GET['quantidade']){
                $retorno['controle'] = 'false';
                $retorno['mensagem'] = 'Este frasco não possui o quantidade necessária para esta aplicação, faço o cadastro atraves da aplicação com 2 códigos.';
            }
            else{
                $retorno['controle'] = 'true';
                $retorno['lote'] = $estoque->lote;
            }
        }
        else{
            $retorno['controle'] = 'false';
            $retorno['mensagem'] = 'Codigo de Barras Inválido';
        }

        echo json_encode($retorno);
    }

    public function abrir_frasco(Request $request){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }

        $medicamento = Medicamento::where('id', $request->medicamento_id)->first();
        $estoque = Estoque::where('medicamento_id', $request->medicamento_id)
        ->where('lote', $request->lote)
        ->where('codigo_barras', $request->codigo_barras)
        ->where('clinica_id', $user->clinica_id)
        ->first();

        if (!$estoque) {
            return redirect()->back()->with('mensagem_erro', 'Estoque não encontrado para o medicamento ' . ($medicamento->nome ?? '') . ' com o lote e código de barras informados.');
        }

        if ($estoque->dt_vencimento && $estoque->dt_vencimento < date('Y-m-d')) {
            return redirect()->back()->with('mensagem_erro', 'O lote ' . $request->lote . ' do medicamento ' . $medicamento->nome . ' está vencido desde ' . dataDbForm($estoque->dt_vencimento) . ' e não pode ser aberto.');
        }

        $clinica = Clinica::where('id', $user->clinica_id)->first();

        $dados = [
            'medicamento_id' => $medicamento->id,
            'procedimento_id' => $request->procedimento_id,
            'clinica_id' => $user->clinica_id,
            'user_id' => $user->id,
            'identificador' => 'xx',
            'dt_cadastro' => date('Y-m-d'),
            'qt_inical' => $medicamento->vasilhame,
            'qt_utilizado' => 0,
            'qt_restante' => $medicamento->vasilhame,
            'lote' => $estoque->lote,
            'codigo_barras' => $request->codigo_barras,
            'situacao' => 'Aberto',
        ];

        EstoqueAberto::create($dados);
        $dados = [
            'clinica_id' => $user->clinica_id,
            'medicamento_id' => $medicamento->id,
            'origem' => 'Procedimento',
            'tipo' => 'Saida',
            'quantidade' => 1,
            'valor' => 0,
            'total' => 0,
            'lote' => $estoque->lote,
            'dt_vencimento' => $estoque->dt_vencimento,
            'codigo_barras' => $request->codigo_barras,
        ];
        Estoque::create($dados);
        return redirect()->route('sistema.dashboard.enfermagem_acessar_procedimento', $request->procedimento_id)->with('mensagem', 'Frasco Aberto');
    }

    public function set_aplicacao_old(Request $request){
        try {
            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            $procedimento_pendente = false;
            foreach($procedimento->aplicacaos as $aplicacao){
                if($aplicacao->situacao == "Aberta" || $aplicacao->situacao == "Pendente"){
                    $var = "controle_pendente_".$aplicacao->medicamento->id;
                    $controle_pendente = $request->$var;
                    if($controle_pendente == "Sim"){
                        $procedimento_pendente = true;
                        $aplicacao->situacao = 'Pendente';
                        $aplicacao->save();
                    }
                    else{
                        $var = 'lote_'.$aplicacao->medicamento->id;
                        $lote = $request->$var;

                        if($aplicacao->medicamento->unidade == "Ampola"){
                            //vamos setar a aplicaçao
                            $estoque_var = Estoque::where('medicamento_id', $aplicacao->medicamento->id)
                            ->where('lote', $lote)->first();
                            $dados = [
                                'aplicacao_id' => $aplicacao->id,
                                'quantidade' => $aplicacao->quantidade,
                                'lote' => $lote,
                                'codigo_barras' => $estoque_var->codigo_barras,
                            ];
                            AplicacaoLote::create($dados);
                            $aplicacao->user_id_aplicacao = $user->id;
                            $aplicacao->situacao = 'Aplicada';
                            $aplicacao->obs = $request->obs_aplicacao;
                            $aplicacao->save();

                            //vamos dar baixa no estoque
                            $estoque = Estoque::where('medicamento_id', $aplicacao->medicamento->id)
                            ->where('lote', $lote)
                            ->first();
                            $dados = [
                                'clinica_id' => $user->clinica_id,
                                'medicamento_id' => $aplicacao->medicamento->id,
                                'origem' => 'Procedimento',
                                'tipo' => 'Saida',
                                'quantidade' => $aplicacao->quantidade,
                                'valor' => 0,
                                'total' => 0,
                                'lote' => $lote,
                                'dt_vencimento' => $estoque->dt_vencimento,
                                'codigo_barras' => $estoque->codigo_barras,
                            ];
                            Estoque::create($dados);
                        }
                        else{
                            $var = "controle_med_".$aplicacao->medicamento->id;
                            $controle = $request->$var;
                            if($lote && $controle != "2_codigo"){
                                $aberto = EstoqueAberto::where('id', $lote)->first();
                                $aberto->qt_utilizado += $aplicacao->quantidade;
                                $aberto->qt_restante -= $aplicacao->quantidade;
                                if($aberto->qt_restante <= 0){
                                    $aberto->situacao = 'Finalizado';
                                }
                                $aberto->save();

                                $dados = [
                                    'aplicacao_id' => $aplicacao->id,
                                    'quantidade' => $aplicacao->quantidade,
                                    'lote' => $aberto->lote,
                                    'codigo_barras' => $aberto->codigo_barras,
                                ];
                                AplicacaoLote::create($dados);
                                $aplicacao->user_id_aplicacao = $user->id;
                                $aplicacao->situacao = 'Aplicada';
                                $aplicacao->obs = $request->obs_aplicacao;
                                $aplicacao->save();
                            }
                            elseif($controle == '2_codigo'){
                                //vamos inserir o 1º estoque aberto
                                $var = "cod_med_1_".$aplicacao->medicamento->id;
                                $codigo_b = $request->$var;
                                $var = "quant_med_1_".$aplicacao->medicamento->id;
                                $quantidade = $request->$var;

                                $aberto = EstoqueAberto::where('codigo_barras', $codigo_b)
                                ->where('medicamento_id', $aplicacao->medicamento->id)
                                ->first();
                                $aberto->qt_utilizado += $quantidade;
                                $aberto->qt_restante -= $quantidade;
                                if($aberto->qt_restante <= 0){
                                    $aberto->situacao = 'Finalizado';
                                }
                                $aberto->save();

                                $dados = [
                                    'aplicacao_id' => $aplicacao->id,
                                    'quantidade' => $quantidade,
                                    'lote' => $aberto->lote,
                                    'codigo_barras' => $aberto->codigo_barras,
                                ];
                                AplicacaoLote::create($dados);

                                //vamos inserir o 2º estoque aberto
                                $var = "cod_med_2_".$aplicacao->medicamento->id;
                                $codigo_b = $request->$var;
                                $var = "quant_med_2_".$aplicacao->medicamento->id;
                                $quantidade = $request->$var;

                                $aberto = EstoqueAberto::where('codigo_barras', $codigo_b)
                                ->where('medicamento_id', $aplicacao->medicamento->id)
                                ->first();
                                $aberto->qt_utilizado += $quantidade;
                                $aberto->qt_restante -= $quantidade;
                                if($aberto->qt_restante <= 0){
                                    $aberto->situacao = 'Finalizado';
                                }
                                $aberto->save();

                                $dados = [
                                    'aplicacao_id' => $aplicacao->id,
                                    'quantidade' => $quantidade,
                                    'lote' => $aberto->lote,
                                    'codigo_barras' => $aberto->codigo_barras,
                                ];
                                AplicacaoLote::create($dados);

                                $aplicacao->situacao = 'Aplicada';
                                $aplicacao->obs = $request->obs_aplicacao;
                                $aplicacao->save();
                            }
                        }
                    }
                }
            }

            $procedimento->situacao = 'Aplicado';
            $procedimento->data_aplicacao = date('Y-m-d');
            if($procedimento_pendente){
                $procedimento->situacao = 'Pendente';
            }
            if($procedimento->st_biopedancia == 'Sim'){
                $procedimento->obs_biopedancia = $request->obs_biopedancia;
            }
            if($procedimento->st_coleta == 'Sim'){
                $procedimento->tp_coleta = $request->tp_coleta;
                $procedimento->obs_coleta = $request->obs_coleta;
            }

            $procedimento->save();
            return redirect()->route('sistema.dashboard')->with('mensagem', 'Aplicação Realizada');
        } catch (\Exception $e) {
            return redirect()->route('sistema.dashboard')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function set_aplicacao(Request $request){
        try {
            $api_aplicacao = false;
            $array_aplicacao = array();
            $api_coleta = false;
            $api_biopedancia = false;

            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            $procedimento_pendente = false;
            foreach($procedimento->aplicacaos as $aplicacao){
                if($aplicacao->situacao == "Aberta" || $aplicacao->situacao == "Pendente"){
                    $var = "controle_pendente_".$aplicacao->medicamento->id;
                    $controle_pendente = $request->$var;
                    if($controle_pendente == "Sim"){
                        $procedimento_pendente = true;
                        $aplicacao->situacao = 'Pendente';
                        $aplicacao->save();
                    }
                    else{
                        $api_aplicacao = true;
                        $array_aplicacao[] = $aplicacao->id;
                        $aplicacao->dt_hr_chegada = $procedimento->dt_hr_chegada;
                        $aplicacao->dt_hr_atendimento = $procedimento->dt_hr_atendimento;

                        $var = 'lote_'.$aplicacao->medicamento->id;
                        $lote = $request->$var;

                        $var = 'codigo_barras_'.$aplicacao->medicamento->id;
                        $codigo_barras = $request->$var;

                        if($aplicacao->medicamento->unidade == "Ampola"){
                            if (empty($lote) || empty($codigo_barras)) {
                                  throw new \Exception('O campo Lote e Código de Barras são obrigatórios para a aplicação de ' . $aplicacao->medicamento->nome);
                            }

                            $estoque = Estoque::where('medicamento_id', $aplicacao->medicamento->id)
                            ->where('lote', $lote)
                            ->where('codigo_barras', $codigo_barras)
                            ->where('clinica_id', $user->clinica_id)
                            ->first();

                            if ($estoque && $estoque->dt_vencimento && $estoque->dt_vencimento < date('Y-m-d')) {
                                throw new \Exception('O lote ' . $lote . ' do medicamento ' . $aplicacao->medicamento->nome . ' está vencido desde ' . dataDbForm($estoque->dt_vencimento) . '.');
                            }

                            //vamos setar a aplicaçao
                            $dados = [
                                'aplicacao_id' => $aplicacao->id,
                                'quantidade' => $aplicacao->quantidade,
                                'lote' => $lote,
                                'codigo_barras' => $codigo_barras,
                            ];
                            AplicacaoLote::create($dados);

                            $aplicacao->user_id_aplicacao = $user->id;
                            $aplicacao->situacao = 'Aplicada';
                            $aplicacao->obs = $request->obs_aplicacao;
                            $aplicacao->save();

                            //vamos dar baixa no estoque
                            $dados = [
                                'clinica_id' => $user->clinica_id,
                                'procedimento_id' => $procedimento->id,
                                'medicamento_id' => $aplicacao->medicamento->id,
                                'origem' => 'Procedimento',
                                'tipo' => 'Saida',
                                'quantidade' => $aplicacao->quantidade,
                                'valor' => 0,
                                'total' => 0,
                                'lote' => $lote,
                                'dt_vencimento' => $estoque->dt_vencimento,
                                'codigo_barras' => $codigo_barras,
                            ];
                            Estoque::create($dados);
                        }
                        elseif($aplicacao->medicamento->unidade == "Miligrama"){
                            $var = "controle_med_".$aplicacao->medicamento->id;
                            $controle = $request->$var;
                            if($controle != "2_codigo" && (empty($lote) || empty($codigo_barras))){
                                throw new \Exception('O campo Lote e Código de Barras são obrigatórios para a aplicação de ' . $aplicacao->medicamento->nome);
                            }
                            if($lote && $codigo_barras && $controle != "2_codigo"){
                                $aberto = EstoqueAberto::where('codigo_barras', $codigo_barras)
                                ->where('clinica_id', $user->clinica_id)
                                ->first();

                                if ($aberto) {
                                    $estoque_lote = Estoque::where('medicamento_id', $aberto->medicamento_id)
                                        ->where('lote', $aberto->lote)
                                        ->where('codigo_barras', $aberto->codigo_barras)
                                        ->where('clinica_id', $aberto->clinica_id)
                                        ->first();
                                    if ($estoque_lote && $estoque_lote->dt_vencimento && $estoque_lote->dt_vencimento < date('Y-m-d')) {
                                        throw new \Exception('O lote ' . $aberto->lote . ' do medicamento ' . $aplicacao->medicamento->nome . ' está vencido desde ' . dataDbForm($estoque_lote->dt_vencimento) . '.');
                                    }
                                }

                                $aberto->qt_utilizado += $aplicacao->quantidade;
                                $aberto->qt_restante -= $aplicacao->quantidade;
                                if($aberto->qt_restante <= 0){
                                    $aberto->situacao = 'Finalizado';
                                }
                                $aberto->save();

                                $dados = [
                                    'aplicacao_id' => $aplicacao->id,
                                    'quantidade' => $aplicacao->quantidade,
                                    'lote' => $aberto->lote,
                                    'codigo_barras' => $aberto->codigo_barras,
                                    'estoque_aberto_id' => $aberto->id,
                                ];
                                AplicacaoLote::create($dados);
                                $aplicacao->user_id_aplicacao = $user->id;
                                $aplicacao->situacao = 'Aplicada';
                                $aplicacao->obs = $request->obs_aplicacao;
                                if($aberto->medicamento_id != $aplicacao->medicamento_id){
                                    $aplicacao->medicamento_id = $aberto->medicamento_id;
                                }
                                $aplicacao->save();
                            }
                            elseif($controle == '2_codigo'){
                                //vamos inserir o 1º estoque aberto
                                $var = "cod_med_1_".$aplicacao->medicamento->id;
                                $codigo_b1 = $request->$var;
                                $var = "quant_med_1_".$aplicacao->medicamento->id;
                                $quantidade1 = $request->$var;

                                //vamos inserir o 2º estoque aberto
                                $var = "cod_med_2_".$aplicacao->medicamento->id;
                                $codigo_b2 = $request->$var;
                                $var = "quant_med_2_".$aplicacao->medicamento->id;
                                $quantidade2 = $request->$var;

                                if (empty($codigo_b1) || empty($codigo_b2)) {
                                    throw new \Exception('O Código de Barras dos dois frascos são obrigatórios para a aplicação de ' . $aplicacao->medicamento->nome);
                                }

                                $aberto = EstoqueAberto::where('codigo_barras', $codigo_b1)
                                ->where('clinica_id', $user->clinica_id)
                                ->first();
                                if ($aberto) {
                                    $estoque_lote1 = Estoque::where('medicamento_id', $aberto->medicamento_id)
                                        ->where('lote', $aberto->lote)
                                        ->where('codigo_barras', $aberto->codigo_barras)
                                        ->where('clinica_id', $aberto->clinica_id)
                                        ->first();
                                    if ($estoque_lote1 && $estoque_lote1->dt_vencimento && $estoque_lote1->dt_vencimento < date('Y-m-d')) {
                                        throw new \Exception('O lote ' . $aberto->lote . ' do primeiro frasco de ' . $aplicacao->medicamento->nome . ' está vencido desde ' . dataDbForm($estoque_lote1->dt_vencimento) . '.');
                                    }
                                }

                                $aberto2_check = EstoqueAberto::where('codigo_barras', $codigo_b2)
                                ->where('clinica_id', $user->clinica_id)
                                ->first();
                                if ($aberto2_check) {
                                    $estoque_lote2 = Estoque::where('medicamento_id', $aberto2_check->medicamento_id)
                                        ->where('lote', $aberto2_check->lote)
                                        ->where('codigo_barras', $aberto2_check->codigo_barras)
                                        ->where('clinica_id', $aberto2_check->clinica_id)
                                        ->first();
                                    if ($estoque_lote2 && $estoque_lote2->dt_vencimento && $estoque_lote2->dt_vencimento < date('Y-m-d')) {
                                        throw new \Exception('O lote ' . $aberto2_check->lote . ' do segundo frasco de ' . $aplicacao->medicamento->nome . ' está vencido desde ' . dataDbForm($estoque_lote2->dt_vencimento) . '.');
                                    }
                                }

                                $aberto->qt_utilizado += $quantidade1;
                                $aberto->qt_restante -= $quantidade1;
                                if($aberto->qt_restante <= 0){
                                    $aberto->situacao = 'Finalizado';
                                }
                                $aberto->save();

                                $dados = [
                                    'aplicacao_id' => $aplicacao->id,
                                    'quantidade' => $quantidade1,
                                    'lote' => $aberto->lote,
                                    'codigo_barras' => $aberto->codigo_barras,
                                    'estoque_aberto_id' => $aberto->id,
                                ];
                                AplicacaoLote::create($dados);

                                $aberto = EstoqueAberto::where('codigo_barras', $codigo_b2)
                                ->where('clinica_id', $user->clinica_id)
                                ->first();
                                $aberto->qt_utilizado += $quantidade2;
                                $aberto->qt_restante -= $quantidade2;
                                if($aberto->qt_restante <= 0){
                                    $aberto->situacao = 'Finalizado';
                                }
                                $aberto->save();

                                $dados = [
                                    'aplicacao_id' => $aplicacao->id,
                                    'quantidade' => $quantidade2,
                                    'lote' => $aberto->lote,
                                    'codigo_barras' => $aberto->codigo_barras,
                                    'estoque_aberto_id' => $aberto->id,
                                ];
                                AplicacaoLote::create($dados);

                                $aplicacao->situacao = 'Aplicada';
                                $aplicacao->obs = $request->obs_aplicacao;

                                if($aberto->medicamento_id != $aplicacao->medicamento_id){
                                    $aplicacao->medicamento_id = $aberto->medicamento_id;
                                }

                                $aplicacao->save();
                            }
                        }
                        elseif($aplicacao->medicamento->unidade == "Procedimento"){
                            $aplicacao->user_id_aplicacao = $user->id;
                            $aplicacao->situacao = 'Aplicada';
                            $aplicacao->obs = $codigo_barras;
                            $aplicacao->save();
                        }
                    }
                }
            }

            $procedimento->situacao = 'Aplicado';
            $procedimento->data_aplicacao = date('Y-m-d');
            $procedimento->dt_hr_finalizacao = date('Y-m-d H:i:s');
            if($procedimento_pendente){
                $procedimento->situacao = 'Aplicação Parcial';
            }
            if($procedimento->st_biopedancia == 'Sim'){
                $api_biopedancia = true;
                $procedimento->obs_biopedancia = $request->obs_biopedancia;
            }
            if($procedimento->st_coleta == 'Sim'){
                $api_coleta = true;
                $procedimento->tp_coleta = $request->tp_coleta;
                $procedimento->obs_coleta = $request->obs_coleta;
            }

            $procedimento->save();

            \App\Http\Controllers\ProcedimentoSistemaController::recalcular_situacao($procedimento->id);

            $api = new ApiFlegowController();

            if($api_aplicacao){
                $api->register_aplicacao($procedimento, 52, $array_aplicacao);
            }

            if($api_biopedancia){
                $api->register_aplicacao($procedimento, 31);
            }

            if($api_coleta){
                if($procedimento->tp_coleta == 'Coleta Completa Feminina'){
                    $api->register_aplicacao($procedimento, 36);
                }
                elseif($procedimento->tp_coleta == 'Coleta Retorno Feminina'){
                    $api->register_aplicacao($procedimento, 37);
                }
                elseif($procedimento->tp_coleta == 'Coleta Completa Masculina'){
                    $api->register_aplicacao($procedimento, 38);
                }
                elseif($procedimento->tp_coleta == 'Coleta Retorno Masculina'){
                    $api->register_aplicacao($procedimento, 39);
                }
                elseif($procedimento->tp_coleta == 'Coleta Cortesia'){
                    $api->register_aplicacao($procedimento, 54);
                }
                elseif($procedimento->tp_coleta == 'Coleta Particular'){
                    $api->register_aplicacao($procedimento, 59);
                }
                elseif($procedimento->tp_coleta == 'Coleta Reduzida'){
                    $api->register_aplicacao($procedimento, 116);
                }
                elseif($procedimento->tp_coleta == 'Coleta Reduzida 2'){
                    $api->register_aplicacao($procedimento, 117);
                }
            }

            return redirect()->route('sistema.dashboard')->with('mensagem', 'Aplicação Realizada');
        } catch (\Exception $e) {
            return redirect()->route('sistema.dashboard')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function add_biopedancia_coleta($paciente_id){
        $paciente = Paciente::where('id', $paciente_id)->first();
        return view('sistema/dashboard/add_biopedancia_coleta', compact('paciente'));
    }

    public function insert_biopedancia_coleta(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }
            $codigo = $request->paciente_id.date('YmdHis');
            $dados = [
                'codigo' => $codigo,
                'nr_procedimento' => '1',
                'clinica_id' => $user->clinica_id,
                'clinica_id_aplicacao' => $user->clinica_id,
                'paciente_id' => $request->paciente_id,
                'data_cad' => date('Y-m-d'),
                'data_aplicacao' => date('Y-m-d'),
                'data_pagamento' => date('Y-m-d'),
                'valor' => '0.00',
                'st_pagamento' => 'Sim',
                'situacao' => 'Fila de Aplicação',
                'medico' => 'Não Informado',
                'tipo_pagamento' => 'Procedimento isento de pagamento.',
                'st_biopedancia' => $request->exames == "Biopedância" || $request->exames == "Biopedância e Coleta" ? 'Sim' : 'Não',
                'st_coleta' => $request->exames == "Coleta" || $request->exames == "Biopedância e Coleta" ? 'Sim' : 'Não',
                'user_id_cadastro' => $user->id,
            ];

            Procedimento::create($dados);
            return redirect()->route('sistema.dashboard')->with('mensagem', 'Biopedância/Coleta Enviada para a lista de atendimento');
        } catch (\Exception $e) {
            return redirect()->route('sistema.dashboard')->with('mensagem_erro', $e->getMessage());
        }

    }

    public function get_lotes_medicamento_mg(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $estoques = Estoque::get_lotes_medicamento_mg($_GET['medicamento_id'],$user->clinica_id);
        $html = "<option value=''>Opções</option>";
        foreach($estoques as $estoque){
            $html .= "<option value='".$estoque['codigo_barras']."' data-lote='".$estoque['lote']."' data-quantidade='".$estoque['estoque']."'>".$estoque['codigo_barras']." - Lote: ".$estoque['lote']." (Saldo: ".$estoque['estoque'].")</option>";
        }
        $retorno['codigos'] = $html;
        echo json_encode($retorno);
    }

    public function filtrar_atrasados(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        //vamos buscar os procedimentos que estao atrasados a mais de 7 dias
        $data_hoje = date('Y-m-d');
        $data =  date('Y-m-d', strtotime("-7 days",strtotime($data_hoje)));

        if($_GET['st_pagamento']){
            $proc_atrasados = Procedimento::where('clinica_id', $user->clinica_id)
            ->where('data_aplicacao', '<=', $data)
            ->where('situacao', 'Agendado')
            ->where('st_pagamento', $_GET['st_pagamento'])
            ->get();
        }
        else{
            $proc_atrasados = Procedimento::where('clinica_id', $user->clinica_id)
            ->where('data_aplicacao', '<=', $data)
            ->where('situacao', 'Agendado')
            ->get();
        }

        $html = "";
        if($proc_atrasados->count() == 0){
            $html = "<tr><td colspan='8'>Nenhum procedimento encontrado</td></tr>";
        }
        else{
            foreach($proc_atrasados as $procedimento) {
                if($_GET['Iniciado']){
                    if($procedimento->get_st_procedimento_iniciado()){
                        $html .= "
                            <tr style='cursor: pointer' onclick='acessa_procedimento($procedimento->id)'>
                                <td>".$procedimento->paciente->nm_paciente."</td>
                                <td>$procedimento->medico</td>
                                <td>".dataDbForm($procedimento->data_cad)."</td>
                                <td>".dataDbForm($procedimento->data_aplicacao)."</td>
                                <td>$dias</td>
                                <td>$procedimento->st_pagamento</td>
                                <td>".valorDbForm($procedimento->valor)."</td>
                                <td>$procedimento->situacao</td>
                            </tr>
                        ";
                    }
                }
                else{
                    $html .= "
                        <tr style='cursor: pointer' onclick='acessa_procedimento($procedimento->id)'>
                            <td>".$procedimento->paciente->nm_paciente."</td>
                            <td>$procedimento->medico</td>
                            <td>".dataDbForm($procedimento->data_cad)."</td>
                            <td>".dataDbForm($procedimento->data_aplicacao)."</td>
                            <td>$dias</td>
                            <td>$procedimento->st_pagamento</td>
                            <td>".valorDbForm($procedimento->valor)."</td>
                            <td>$procedimento->situacao</td>
                        </tr>
                    ";
                }
            }
        }
        $retorno['html'] = $html;

        echo json_encode($retorno);
    }

    public function fila_atendimento(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }

        $procedimentos_aguardando = Procedimento::where('clinica_id_aplicacao', $user->clinica_id)
        ->where('situacao','Fila de Aplicação')
        ->orderBy('updated_at')
        ->get();

        $procedimentos_atendimento = Procedimento::where('clinica_id_aplicacao', $user->clinica_id)
        ->where('situacao','Atendimento')
        ->orderBy('updated_at')
        ->get();

        $procedimentos_aplicadas = Procedimento::where('clinica_id_aplicacao', $user->clinica_id)
        ->where('situacao','Aplicado')
        ->where('data_aplicacao',date('Y-m-d'))
        ->orderBy('updated_at')
        ->get();

        return view('sistema/dashboard/fila_atendimento', compact('procedimentos_aguardando','procedimentos_atendimento','procedimentos_aplicadas','user'));
    }


}
