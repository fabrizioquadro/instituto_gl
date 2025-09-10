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
            return view('sistema/dashboard/index_enfermeira', compact('procedimentos_aguardando',
            'procedimentos_atendimento','procedimentos_aplicadas','user'));
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
                $dados_pesquisa['paciente_id'] = $paciente_id;
                $dados_pesquisa['paciente_controle'] = $paciente ? $paciente->id : '';

                $procedimentos = Procedimento::lista_procedimentos_filtro($dados_pesquisa);
            }
            else{
                $data = date('Y-m-d');

                $dados_pesquisa['dt_procedimentos'] = $data;
                $dados_pesquisa['st_pagamento'] = '';
                $dados_pesquisa['situacao'] = 'Agendado';
                $dados_pesquisa['paciente_controle'] = '';

                $procedimentos = Procedimento::where('clinica_id', $user->clinica_id)
                ->where('data_aplicacao', $data)
                ->where('situacao', 'Agendado')
                ->where('situacao', 'Pendente')
                ->get();
            }

            //vamos buscar os procedimentos que estao atrasados a mais de 7 dias
            $data_hoje = date('Y-m-d');
            $data =  date('Y-m-d', strtotime("-7 days",strtotime($data_hoje)));

            $proc_atrasados = Procedimento::where('clinica_id', $user->clinica_id)
            ->where('data_aplicacao', '<=', $data)
            ->where('situacao', 'Agendado')
            ->get();

            return view('sistema/dashboard/index', compact('procedimentos','paciente_id','paciente','dados_pesquisa','proc_atrasados'));
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

        if($procedimento->st_pagamento != "Sim" && !$procedimento->autorizador_sem_pagamento){
            echo "Esse procedimento não esta pago para fazer a aplicação";
            die();
        }

        if($procedimento->situacao != "Fila de Aplicação" && $procedimento->user_id_aplicacao != $user->id){
            return redirect()->route('sistema.dashboard')->with('mensagem_erro', 'Este Paciente já esta sendo atendido!');
        }
        $procedimento->situacao = "Atendimento";
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
        if(isset($_GET['controle'])){
            return view('sistema/dashboard/enfermeira_acessar_procedimento_new', compact('procedimento','user','controle','procedimentos_vinculados','nascimento'));
        }
        else{
            return view('sistema/dashboard/enfermeira_acessar_procedimento', compact('procedimento','user','controle','procedimentos_vinculados','nascimento'));
        }
    }

    public function busca_lote_por_codigo(){
        $estoque = Estoque::where('codigo_barras', $_GET['codigo'])->first();
        if($estoque){
            $retorno['lote'] = $estoque->lote;
        }
        else{
            $retorno['lote'] = '';
        }
        echo json_encode($retorno);
    }

    public function busca_lote_por_codigo_frasco(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }

        $estoque = EstoqueAberto::where('clinica_id', $user->clinica_id)
        ->where('medicamento_id', $_GET['medicamento_id'])
        ->where('codigo_barras', $_GET['codigo'])
        ->where('situacao','Aberto')
        ->first();

        if($estoque){
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
        $estoque = Estoque::where('medicamento_id', $medicamento->id)->where('codigo_barras', $request->codigo_barras)->first();
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
                                $var = 'codigo_barras_'.$aplicacao->medicamento->id;
                                $codigo_barras = $request->$var;
                                $aberto = EstoqueAberto::where('codigo_barras', $codigo_barras)->first();
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
            $html .= "<option value='".$estoque['codigo_barras']."' data-quantidade='".$estoque['estoque']."'>".$estoque['codigo_barras']."</option>";
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


}
