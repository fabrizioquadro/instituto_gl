<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Procedimento;
use App\Models\ProcedimentoAnexo;
use App\Models\ProcedimentoObservacao;
use App\Models\Aplicacao;
use App\Models\AplicacaoLote;
use App\Models\Medicamento;
use App\Models\Paciente;
use App\Models\Financeiro;
use App\Models\FinanceiroProcedimento;
use App\Models\FinanceiroFormasPagamento;
use App\Models\Administrador;
use App\Models\Estoque;
use App\Models\EstoqueAberto;
use App\Models\Combo;
use App\Models\ComboMedicamento;
use App\Models\ProcedimentoLog;
use Illuminate\Support\Facades\Hash;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Helpers\GerarPdf;

class ProcedimentoSistemaController extends Controller
{
    public function index(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        //$procedimentos = Procedimento::where('nr_procedimento','1')->get();;
        //$pacientes = Paciente::all()->sortBy('nm_paciente');

        return view('sistema/procedimentos/index');
    }

    public function index_pesq(){
        $requestData= $_REQUEST;

        //Obtendo registros de número total sem qualquer pesquisa
        $qt_linhas = Procedimento::where('nr_procedimento','1')->count();
        $retorno = Procedimento::index_pesq($requestData);

        $procedimentos = $retorno['procedimentos'];
        $totalFiltered = $retorno['totalFiltered'];

        $dados = array();
        foreach($procedimentos as $procedimento){
            $dado = array();
            $st_procedimento = $procedimento->get_st_procedimento();
            if($st_procedimento == "Aberto"){
                $situacao = '<span class="badge rounded-pill bg-label-warning">'.$st_procedimento.'</span>';
            }
            elseif($st_procedimento == "Finalizado"){
                $situacao = '<span class="badge rounded-pill bg-label-success">'.$st_procedimento.'</span>';
            }
            elseif($st_procedimento == "Cancelado"){
                $situacao = '<span class="badge rounded-pill bg-label-danger">'.$st_procedimento.'</span>';
            }

            $st_pagamento = $procedimento->get_st_pagamento();
            if($st_pagamento == 'Aberto'){
                $st_pagamento = "<span class='badge bg-danger'>$st_pagamento</span>";
            }
            elseif($st_pagamento == 'Total'){
                $st_pagamento = "<span class='badge bg-success'>$st_pagamento</span>";
            }
            elseif($st_pagamento == 'Parcial'){
                $st_pagamento = "<span class='badge bg-warning'>$st_pagamento</span>";
            }

            $botao = "
            <div class='dropdown'>
                <button type='button' class='btn p-0 dropdown-toggle hide-arrow show' data-bs-toggle='dropdown' aria-expanded='true'>
                    <i class='mdi mdi-dots-vertical'></i>
                </button>
                <div class='dropdown-menu' data-popper-placement='bottom-end'>
                    <a class='dropdown-item waves-effect' href='".route('sistema.procedimentos.acessar_grupo', $procedimento->codigo)."'><i class='mdi mdi-eye me-1'></i> Acessar</a>
                    <a class='dropdown-item waves-effect' href='".route('sistema.procedimentos.imprimir_paciente', $procedimento->codigo)."'><i class='mdi mdi-cloud-print me-1'></i> Imprimir Prontuário</a>
                    <a class='dropdown-item waves-effect' href='".route('sistema.procedimentos.imprimir_cadastro', $procedimento->codigo)."'><i class='mdi mdi-folder-open me-1'></i> Imprimir Cadastro</a>";
                    if($st_procedimento != "Finalizado" && $st_procedimento != "Cancelado"){
                        $botao .= "<a class='dropdown-item waves-effect' href='".route('sistema.procedimentos.cancelar', $procedimento->codigo)."'><i class='mdi mdi-cancel me-1'></i> Cancelar</a>";
                    }
                    $botao .= "
                    <a class='dropdown-item waves-effect' href='".route('sistema.procedimentos.editar_medico', $procedimento->codigo)."'><i class='mdi mdi-pencil me-1'></i> Editar Médico</a>
                </div>
            </div>
            ";
            $dado[] = $botao;
            $dado[] = "<span style='display: none'>".strtotime($procedimento->data_cad)."</span>".dataDbForm($procedimento->data_cad);
            $dado[] = $procedimento->paciente->nm_paciente;
            $dado[] = $procedimento->paciente->dt_nascimento ? dataDbForm($procedimento->paciente->dt_nascimento) : '';
            $dado[] = $procedimento->codigo;
            $dado[] = $procedimento->get_nr_semanas();
            $dado[] = $procedimento->medico;
            $dado[] = $procedimento->tipo_atendimento;
            $dado[] = dataDbForm($procedimento->data_aplicacao);
            $dado[] = $st_pagamento;
            $dado[] = $situacao;
            $dado[] = $procedimento->cadastrante ? $procedimento->cadastrante->nome : '';
            
            $ultima_edicao = $procedimento->get_ultima_edicao();
            $dado[] = $ultima_edicao ? date('d/m/Y H:i:s', strtotime($ultima_edicao)) : '-';

            $dados[] = $dado;
        }

        $json_data = array(
	        "draw" => intval( $requestData['draw'] ),//para cada requisição é enviado um número como parâmetro
        	"recordsTotal" => intval( $qt_linhas ),  //Quantidade de registros que há no banco de dados
        	"recordsFiltered" => intval( $totalFiltered ), //Total de registros quando houver pesquisa
        	"data" => $dados   //Array de dados completo dos dados retornados da tabela
        );

        echo json_encode($json_data);  //enviar dados como formato json
    }

    public function acessar_grupo($codigo, $retorno = 'null'){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $procedimentos = Procedimento::where('codigo',$codigo)->with('logs')->get();
        //$pacientes = Paciente::all()->sortBy('nm_paciente');

        return view('sistema/procedimentos/index_grupo', compact('procedimentos','codigo'));
    }

    public function adicionar($retorno = null){
        $api = api();
        $medicos = $api->get_medicos();
        $pacientes = Paciente::all()->sortBy('nm_paciente');
        $medicamentos = Medicamento::all()->sortBy('nome');
        $combos = Combo::all()->sortBy('nome');
        //if($_GET && $_GET['controle'] == 'true'){
        //    return view('sistema/procedimentos/adicionar_new', compact('pacientes','medicamentos','medicos','retorno','combos'));
        //    exit();
        //}
        return view('sistema/procedimentos/adicionar', compact('pacientes','medicamentos','medicos','retorno','combos'));
    }

    public function adicionar_grupo($codigo){
        $api = api();
        $medicos = $api->get_medicos();
        $pacientes = Paciente::all()->sortBy('nm_paciente');
        $medicamentos = Medicamento::all()->sortBy('nome');
        $retorno = null;
        $combos = Combo::all()->sortBy('nome');
        $procedimento_origem = Procedimento::where('codigo', $codigo)->first();
        $paciente = $procedimento_origem->paciente;
        return view('sistema/procedimentos/adicionar', compact('pacientes','medicamentos','medicos','retorno','codigo','combos','paciente'));
    }

    public function insert(Request $request){
        try {
            $precisa_anexo = false;
            for($i=1 ; $i<= $request->contador_procedimentos ; $i++){
                $var_contador = "contador_medicamentos_".$i;
                $contador = $request->$var_contador;
                if($contador){
                    for($j=1 ; $j<=$contador ; $j++){
                        $var_med = "medicamento_id_".$i."_".$j;
                        $medicamento_id = $request->$var_med;
                        if($medicamento_id){
                            $medicamento = Medicamento::find($medicamento_id);
                            if($medicamento && in_array($medicamento->unidade, ['Ampola', 'Miligrama'])){
                                $precisa_anexo = true;
                                break 2;
                            }
                        }
                    }
                }
            }

            if($precisa_anexo && !$request->hasFile('anexos')) {
                return redirect()->back()->with('mensagem_erro', 'É obrigatório inserir o anexo (prescrição médica) pois o procedimento contém medicamentos em Ampola ou Miligrama.');
            }

            $array_procedimentos = array();
            $controle_update = false;

            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            if($request->codigo){
                $codigo = $request->codigo;
                $nr_procedimento = Procedimento::where('codigo', $codigo)->count();
                $proc_origem = Procedimento::where('codigo', $codigo)->first();
                $medico = $proc_origem->medico;
                $paciente_id = $proc_origem->paciente_id;
                $controle_update = true;
            }
            else{
                $codigo = $request->paciente_id.date('YmdHis');
                $nr_procedimento = 0;
                $medico = $request->medico;
                $paciente_id = $request->paciente_id;
            }

            $paciente = Paciente::where('id', $paciente_id)->first();
            if($request->paciente_obs){
                $paciente->obs = $request->paciente_obs;
                $paciente->save();
            }

            for($i=1 ; $i<= $request->contador_procedimentos ; $i++){
                //vamos cadastrar o procedimento
                $var = 'data_aplicacao_'.$i;
                $data_aplicacao = $request->$var;
                if($data_aplicacao){
                    $nr_procedimento++;

                    $var = "obs_".$i;
                    $obs = $request->$var;

                    $var = "semana_sem_aplicacao_".$i;
                    $semana_sem_aplicacao = $request->$var;

                    if($semana_sem_aplicacao == 'true'){
                        $dados = [
                            'codigo' => $codigo,
                            'nr_procedimento' => $nr_procedimento,
                            'clinica_id' => $user->clinica_id,
                            'clinica_id_aplicacao' => $user->clinica_id,
                            'paciente_id' => $paciente_id,
                            'data_cad' => date('Y-m-d'),
                            'data_aplicacao' => $data_aplicacao,
                            'valor' => 0.00,
                            'st_pagamento' => 'Sim',
                            'situacao' => 'Semana Sem Aplicação',
                            'medico' => $medico,
                            'obs' => $obs,
                            'semana_sem_aplicacao' => 'Sim',
                            'user_id_cadastro' => $user->id,
                            'inicio_cadastro' => $request->inicio_cadastro,
                            'agendamento' => $request->agendamento,
                            'tipo_atendimento' => $request->tipo_atendimento,
                        ];
                        $procedimento = Procedimento::create($dados);

                        if($request->hasFile('anexos')){
                            foreach($request->file('anexos') as $arquivo){
                                if($arquivo->isValid()){
                                    $extensao = $arquivo->extension();
                                    $nm_arquivo = str_replace(".$extensao", "", $arquivo->getClientOriginalName());
                                    $arquivo_link = $arquivo->getClientOriginalName();
                                    $arquivo->move(public_path('procedimentos/'.$procedimento->id."/anexos/"), $arquivo_link);

                                    $dados_arq = [
                                        'procedimento_id' => $procedimento->id,
                                        'nm_anexo' => $nm_arquivo,
                                        'anexo' => $arquivo_link,
                                    ];

                                    ProcedimentoAnexo::create($dados_arq);
                                }
                            }
                        }
                    }
                    else{
                        $var = "total_procedimento_".$i;
                        $valor = $request->$var;

                        $dados = [
                            'codigo' => $codigo,
                            'nr_procedimento' => $nr_procedimento,
                            'clinica_id' => $user->clinica_id,
                            'clinica_id_aplicacao' => $user->clinica_id,
                            'paciente_id' => $paciente_id,
                            'data_cad' => date('Y-m-d'),
                            'data_aplicacao' => $data_aplicacao,
                            'valor' => valorFormDb($valor),
                            'st_pagamento' => 'Não',
                            'situacao' => 'Agendado',
                            'medico' => $medico,
                            'obs' => $obs,
                            'semana_sem_aplicacao' => 'Não',
                            'user_id_cadastro' => $user->id,
                            'inicio_cadastro' => $request->inicio_cadastro,
                            'agendamento' => $request->agendamento,
                            'tipo_atendimento' => $request->tipo_atendimento,
                        ];
                        $procedimento = Procedimento::create($dados);

                        $array_procedimentos[] = $procedimento;

                        $var = "contador_medicamentos_".$i;
                        $contador = $request->$var;

                        $controle_sem_aplicacao = true;

                        for($j=1 ; $j<=$contador ; $j++){
                            $var = "medicamento_id_".$i."_".$j;
                            $medicamento_id = $request->$var;
                            if($medicamento_id){
                                $var = "quantidade_".$i."_".$j;
                                $quantidade = $request->$var;

                                $var = "valor_".$i."_".$j;
                                $valor = $request->$var;

                                $var = "total_".$i."_".$j;
                                $total = $request->$var;

                                $medicamento = Medicamento::where('id', $medicamento_id)->first();
                                if($medicamento->aplicacao == 'Sim'){
                                    $dados_situacao = 'Aberta';
                                    $controle_sem_aplicacao = false;
                                }
                                else{
                                    $dados_situacao = 'Aplicada';
                                }

                                $var = "is_soro_".$i."_".$j;
                                $is_soro = $request->$var ?? 0;

                                $dados = [
                                    'procedimento_id' => $procedimento->id,
                                    'medicamento_id' => $medicamento_id,
                                    'quantidade' => $quantidade,
                                    'valor' => valorFormDb($valor),
                                    'total' => valorFormDb($total),
                                    'situacao' => $dados_situacao,
                                    'is_soro' => $is_soro,
                                ];
                                Aplicacao::create($dados);
                            }
                        }

                        if($controle_sem_aplicacao){
                            $procedimento->situacao = 'Aplicado';
                            $procedimento->user_id_aplicacao = $user->id;
                            $procedimento->save();
                        }

                        if($request->hasFile('anexos')){
                            foreach($request->file('anexos') as $arquivo){
                                if($arquivo->isValid()){
                                    $extensao = $arquivo->extension();
                                    $nm_arquivo = str_replace(".$extensao", "", $arquivo->getClientOriginalName());
                                    $arquivo_link = $arquivo->getClientOriginalName();
                                    $arquivo->move(public_path('procedimentos/'.$procedimento->id."/anexos/"), $arquivo_link);

                                    $dados_arq = [
                                        'procedimento_id' => $procedimento->id,
                                        'nm_anexo' => $nm_arquivo,
                                        'anexo' => $arquivo_link,
                                    ];

                                    ProcedimentoAnexo::create($dados_arq);
                                }
                            }
                        }
                    }
                }
            }

            $this->recalcular_semanas_grupo($codigo);

            if($controle_update){
                FinanceiroSistemaController::atualiza_financeiro_procedimento($codigo);
                return redirect()->route('sistema.procedimentos.acessar_grupo', $codigo)->with('mensagem', 'Procedimentos Adicionados!');
            }
            else{
                $retorno = $request->retorno;
                $medico = $request->medico;
                return view('sistema/procedimentos/financeiro', compact('array_procedimentos','paciente','retorno','medico'));
            }
            //return redirect()->route('sistema.procedimentos.financeiros', $financeiro->id);
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function acessar($id, $retorno = null){
        $procedimento = Procedimento::where('id', $id)->first();
        $procedimentos_vinculados = Procedimento::where('codigo', $procedimento->codigo)
        ->where('id','<>', $procedimento->id)
        ->orderBy('nr_procedimento')
        ->get();

        //vamos verificar se é a ultima ou penultimo procedimento
        $controle = $procedimentos_vinculados->count() + 1; //adiciona 1 para compensar o procedimento em questao

        $controle_aviso_coleta = '';
        if($controle == $procedimento->nr_procedimento){
            $controle_aviso_coleta = 'ultimo';
        }
        else{
            $controle -= 1;
            if($controle == $procedimento->nr_procedimento){
                $controle_aviso_coleta = 'penultimo';
            }
        }
        //$financeiro = null;
        //if($procedimento->st_pagamento == 'Sim'){
        //    $financeiro_id = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first()->financeiro_id;
        //    $financeiro = Financeiro::where('id', $financeiro_id)->first();
        //}

        //vamos buscar o financeiro
        $fin_proc = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first();
        if(!$fin_proc){
            //se entrar aqui não foi gerado o financeiro
            $controle_financeiro = true;
            $procedimentos_vinculados = Procedimento::where('codigo', $procedimento->codigo)->get();
            foreach($procedimentos_vinculados as $proc){
                if($controle_financeiro){
                    $var = FinanceiroProcedimento::where('procedimento_id', $proc->id)->first();
                    if($var){
                        $dados = [
                            'financeiro_id' => $var->financeiro_id,
                            'procedimento_id' => $procedimento->id,
                        ];
                        $fin_proc = FinanceiroProcedimento::create($dados);
                        $controle_financeiro = false;
                    }
                }
            }
        }

        if(!$fin_proc){
            //se entrar aqui é que nenhum financeiro foi criado
            //$request = new Request();
            //$this->financeiros($request, $procedimento);
            FinanceiroSistemaController::atualiza_financeiro_procedimento($procedimento->codigo);
        }

        $fin_proc = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first();
        $financeiro = Financeiro::where('id', $fin_proc->financeiro_id)->first();

        $logs = $procedimento->logs()->orderBy('created_at','desc')->get();
        $observacoes = $procedimento->observacoes_procedimento()->orderBy('created_at','desc')->get();

        return view('sistema/procedimentos/acessar', compact('procedimento','retorno','procedimentos_vinculados','controle_aviso_coleta','financeiro','logs', 'observacoes'));
    }

    public function salvar_observacao(Request $request){
        try {
            $user = auth()->user() ?? session()->get('user');
            
            ProcedimentoObservacao::create([
                'procedimento_id' => $request->procedimento_id,
                'user_id' => $user->id ?? null,
                'observacao' => $request->observacao
            ]);

            ProcedimentoLog::registrar($request->procedimento_id, 'Procedimento', 'Observação adicionada');

            return redirect()->back()->with('mensagem', 'Observação salva com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro ao salvar observação: '.$e->getMessage());
        }
    }

    public function setar_pagamento(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            if($procedimento->st_pagamento == "Pendente"){
                $procedimento->st_pagamento = 'Não';
                $procedimento->save();
            }
            $var = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first();
            $financeiro = Financeiro::where('id', $var->financeiro_id)->first();
            $financeiro->obs_pagamento = $financeiro->obs_pagamento.' / '.$request->obs_pagamento;
            $financeiro->save();

            $valor_pagamento = 0;
            for($i=1 ; $i<=$request->contador_formas ; $i++){
                $var = "forma_pagamento_".$i;
                $forma_pagamento = $request->$var;

                if($forma_pagamento == "Crédito"){
                    $var = "parcelas_".$i;
                    $parcelas = $request->$var;
                }
                else{
                    $parcelas = 1;
                }

                $var = "vl_pagamento_".$i;
                $vl_pagamento = $request->$var;

                $var = "id_pagamento_".$i;
                $id_pagamento = $request->$var;

                if($forma_pagamento && $vl_pagamento){
                    $dados = [
                        'financeiro_id' => $financeiro->id,
                        'forma_pagamento' => $forma_pagamento,
                        'parcelas' => $parcelas,
                        'vl_pagamento' => valorFormDb($vl_pagamento),
                        'id_pagamento' => $id_pagamento,
                        'user_id_cadastro' => $user->id,
                    ];

                    FinanceiroFormasPagamento::create($dados);
                    $valor_pagamento += valorFormDb($vl_pagamento);
                }
            }

            FinanceiroSistemaController::atualiza_financeiro_procedimento($procedimento->codigo);

            return redirect()->route('sistema.procedimentos.acessar', [$procedimento->id, $request->retorno])->with('mensagem','Pagamento Cadastrado');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos.acessar', $procedimento->id)->with('mensagem_erro',$e->getMessage());
        }
    }

    public function setar_pagamento_pendente($id){
        try {
            $procedimento = Procedimento::where('id', $id)->first();
            $procedimento->st_pagamento = 'Pendente';
            $procedimento->save();

            FinanceiroSistemaController::atualiza_financeiro_procedimento($procedimento->codigo);

            return redirect()->route('sistema.procedimentos.acessar', $procedimento->id)->with('mensagem', 'Pagamento setado como pendente.');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos.acessar', $procedimento->id)->with('mensagem_erro', $e->getMessage());
        }
    }

    public function setar_pagamento_old(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            //if($request->forma_pagamento == "Crédito"){
            //    $parcelas = $request->parcelas;
            //}
            //else{
            //    $parcelas = '1';
            //}
            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();

            $vl_procedimentos = $procedimento->valor;

            $dados = [
                'clinica_id' => $user->clinica_id,
                'paciente_id' => $procedimento->paciente_id,
                'medico' => $procedimento->medico,
                'dt_pagamento' => date('Y-m-d'),
                'vl_consulta' => '0.00',
                'vl_procedimentos' => $vl_procedimentos,
                'vl_desconto' => '0.00',
                'vl_pagamento' => $vl_procedimentos,
                'tipo_pagamento' => 'teste',
                'forma_pagamento' => 'teste',//$request->forma_pagamento,
                'parcelas' => '1',//$parcelas,
                'obs_pagamento' => $request->obs_pagamento,
            ];

            $financeiro = Financeiro::create($dados);

            $dados = [
                'financeiro_id' => $financeiro->id,
                'procedimento_id' => $procedimento->id,
            ];

            FinanceiroProcedimento::create($dados);

            $procedimento->st_pagamento = 'Sim';
            $procedimento->data_pagamento = date('Y-m-d');
            $procedimento->tipo_pagamento = 'Parcial';
            $procedimento->forma_pagamento = $request->forma_pagamento;
            $procedimento->parcelas = '1';
            $procedimento->obs_pagamento = $request->obs_pagamento;
            $procedimento->save();

            for($i=1 ; $i<=$request->contador_formas ; $i++){
                $var = "forma_pagamento_".$i;
                $forma_pagamento = $request->$var;

                if($forma_pagamento == "Crédito"){
                    $var = "parcelas_".$i;
                    $parcelas = $request->$var;
                }
                else{
                    $parcelas = 1;
                }

                $var = "vl_pagamento_".$i;
                $vl_pagamento = $request->$var;

                $var = "id_pagamento_".$i;
                $id_pagamento = $request->$var;

                if($forma_pagamento && $vl_pagamento){
                    $dados = [
                        'financeiro_id' => $financeiro->id,
                        'forma_pagamento' => $forma_pagamento,
                        'parcelas' => $parcelas,
                        'vl_pagamento' => valorFormDb($vl_pagamento),
                        'id_pagamento' => $id_pagamento,
                        'user_id_cadastro' => $user->id,
                    ];

                    FinanceiroFormasPagamento::create($dados);

                    ProcedimentoLog::registrar($procedimento->id, 'Financeiro', "Pagamento de R$ ".valorDbForm(valorFormDb($vl_pagamento))." ($forma_pagamento) adicionado.");
                }
            }

            return redirect()->route('sistema.procedimentos.acessar', [$procedimento->id, $request->retorno])->with('mensagem','Pagamento Cadastrado');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos.acessar', $procedimento->id)->with('mensagem_erro',$e->getMessage());
        }
    }

    public function enviar_fila_aplicacao(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }
            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            $procedimento->clinica_id_aplicacao = $user->clinica_id;
            $procedimento->situacao = 'Fila de Aplicação';
            $procedimento->data_aplicacao = date('Y-m-d');
            $procedimento->st_biopedancia = $request->exames == "Biopedância" || $request->exames == "Biopedância e Coleta" ? 'Sim' : 'Não';
            $procedimento->st_coleta = $request->exames == "Coleta" || $request->exames == "Biopedância e Coleta" ? 'Sim' : 'Não';
            $procedimento->st_retirada = $request->retirada == "Sim" ? 'Sim' : 'Não';
            $procedimento->obs_retirada = $request->obs_retirada ? $request->obs_retirada : '';
            $procedimento->consulta_tratamento_agendada = $request->consulta_tratamento_agendada ? $request->consulta_tratamento_agendada : '';
            $procedimento->dt_hr_chegada = date('Y-m-d H:i:s');
            $procedimento->save();

            if($request->retorno == 'sistema_dashboard'){
                return redirect()->route('sistema.dashboard')->with('mensagem','Procedimento Enviado para Fila de Aplicação');
            }
            elseif($request->retorno == 'adm_dashboard'){
                return redirect()->route('adm.sistema.dashboard')->with('mensagem','Procedimento Enviado para Fila de Aplicação');
            }
            else{
                return redirect()->route('sistema.procedimentos')->with('mensagem','Procedimento Enviado para Fila de Aplicação');
            }

        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos.acessar', $procedimento->id)->with('mensagem_erro',$e->getMessage());
        }
    }

    public function enviar_fila_aplicacao_sem_pagamento(Request $request){
        try {
            //vamos veridicar o administrador
            $autorizador = Administrador::where('email',$request->autorizador_email)->where('st_usuario', 'Ativo')->first();
            if(!$autorizador){
                return redirect()->back()->with('mensagem_erro', "Autorizador inválido");
                die();
            }

            if(!Hash::check($request->autorizador_senha, $autorizador->password)){
                return redirect()->back()->with('mensagem_erro', "Autorizador senha inválida");
                die();
            }


            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }
            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            $procedimento->clinica_id_aplicacao = $user->clinica_id;
            $procedimento->situacao = 'Fila de Aplicação';
            $procedimento->data_aplicacao = date('Y-m-d');
            $procedimento->st_biopedancia = $request->exames == "Biopedância" || $request->exames == "Biopedância e Coleta" ? 'Sim' : 'Não';
            $procedimento->st_coleta = $request->exames == "Coleta" || $request->exames == "Biopedância e Coleta" ? 'Sim' : 'Não';
            $procedimento->st_retirada = $request->retirada == "Sim" ? 'Sim' : 'Não';
            $procedimento->obs_retirada = $request->obs_retirada ? $request->obs_retirada : '';
            $procedimento->autorizador_sem_pagamento = $autorizador->id;
            $procedimento->consulta_tratamento_agendada = $request->consulta_tratamento_agendada ? $request->consulta_tratamento_agendada : '';
            $procedimento->dt_hr_chegada = date('Y-m-d H:i:s');
            $procedimento->save();

            if($request->retorno == 'sistema_dashboard'){
                return redirect()->route('sistema.dashboard')->with('mensagem','Procedimento Enviado para Fila de Aplicação');
            }
            elseif($request->retorno == 'adm_dashboard'){
                return redirect()->route('adm.sistema.dashboard')->with('mensagem','Procedimento Enviado para Fila de Aplicação');
            }
            else{
                return redirect()->route('sistema.procedimentos')->with('mensagem','Procedimento Enviado para Fila de Aplicação');
            }

        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos.acessar', $procedimento->id)->with('mensagem_erro',$e->getMessage());
        }
    }

    public function financeiros(Request $request, $procedimento = false){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            //vamos gerar o financeiro
            $vl_procedimentos = 0;
            if(!$procedimento){
                $controle_request = true;
                $procedimentos = $request->procedimentos ? $request->procedimentos : [];
                $paciente_id = $request->paciente_id;
                $medico = $request->medico;
                $vl_consulta = $request->vl_consulta;
                $vl_desconto = $request->vl_desconto;
                $vl_adicional = $request->vl_adicional;
                $vl_pagamento = $request->vl_pagamento;
                $obs_pagamento = $request->obs_pagamento;
            }
            elseif($procedimento){
                $controle_request = false;
                $paciente_id = $procedimento->paciente_id;
                $medico = $procedimento->medico;
                $vl_consulta = '0,00';
                $vl_desconto = '0,00';
                $vl_adicional = '0,00';
                $vl_pagamento = '0,00';
                $obs_pagamento = '';

                $res = Procedimento::where('codigo', $procedimento->codigo)->get();
                $procedimentos = array();
                foreach($res as $linha){
                    $procedimentos[] = $linha->id;
                }
            }
            else{
                $controle_request = true;
                $procedimentos = array();
            }

            if($procedimentos){
                foreach($procedimentos as $procedimento_id){
                    $procedimento = Procedimento::where('id', $procedimento_id)->first();
                    $vl_procedimentos += $procedimento->valor;
                }
            }

            $dados = [
                'clinica_id' => $user->clinica_id,
                'paciente_id' => $paciente_id,
                'medico' => $medico,
                'dt_pagamento' => date('Y-m-d'),
                'vl_consulta' => valorFormDb($vl_consulta),
                'vl_consulta_pagamento' => 0.00,
                'vl_procedimentos' => $vl_procedimentos,
                'vl_desconto' => valorFormDb($vl_desconto),
                'vl_adicional' => valorFormDb($vl_adicional),
                'vl_pagamento' => valorFormDb($vl_pagamento),
                'tipo_pagamento' => 'teste',
                'forma_pagamento' => 'teste',//$request->forma_pagamento,
                'parcelas' => 1,//$parcelas,
                'obs_pagamento' => $obs_pagamento,
            ];

            $financeiro = Financeiro::create($dados);

            $valor_pago = 0;

            if($controle_request){
                for($i=1 ; $i<=$request->contador_formas ; $i++){
                    $var = "forma_pagamento_".$i;
                    $forma_pagamento = $request->$var;

                    if($forma_pagamento == "Crédito"){
                        $var = "parcelas_".$i;
                        $parcelas = $request->$var;
                    }
                    else{
                        $parcelas = 1;
                    }

                    $var = "vl_pagamento_".$i;
                    $vl_pagamento = $request->$var;

                    $var = "id_pagamento_".$i;
                    $id_pagamento = $request->$var;

                    if($forma_pagamento && $vl_pagamento){
                        $dados = [
                            'financeiro_id' => $financeiro->id,
                            'forma_pagamento' => $forma_pagamento,
                            'parcelas' => $parcelas,
                            'vl_pagamento' => valorFormDb($vl_pagamento),
                            'id_pagamento' => $id_pagamento,
                            'user_id_cadastro' => $user->id,
                        ];

                        FinanceiroFormasPagamento::create($dados);
                        $valor_pago += valorFormDb($vl_pagamento);
                    }
                }
            }

            if($valor_pago > 0){
                if($valor_pago >= $financeiro->vl_consulta){
                    $vl_consulta_pagamento = $financeiro->vl_consulta;
                }
                elseif($valor_pago < $financeiro->vl_consulta){
                    $vl_consulta_pagamento = $valor_pago;
                }
                $valor_pago -= $vl_consulta_pagamento;
                $financeiro->vl_consulta_pagamento = $vl_consulta_pagamento;
                $financeiro->save();
            }

            //para aplicar o desconto nos procedimentos e os acrecimos
            $valor_pago += $financeiro->vl_desconto - $financeiro->vl_adicional;

            if($procedimentos){
                foreach($procedimentos as $procedimento_id){
                    $procedimento = Procedimento::where('id', $procedimento_id)->first();

                    $dados = [
                        'financeiro_id' => $financeiro->id,
                        'procedimento_id' => $procedimento->id,
                    ];
                    FinanceiroProcedimento::create($dados);

                    if($valor_pago > 0){
                        if($valor_pago >= $procedimento->valor){
                            $st_pagamento = 'Sim';
                            $vl_pago = $procedimento->valor;
                        }
                        elseif($valor_pago < $procedimento->valor){
                            $st_pagamento = 'Parcial';
                            $vl_pago = $valor_pago;
                        }

                        $valor_pago -= $vl_pago;

                        $procedimento->st_pagamento = $st_pagamento;
                        $procedimento->tipo_pagamento = $financeiro->tipo_pagamento;
                        $procedimento->forma_pagamento = $financeiro->forma_pagamento;
                        $procedimento->parcelas = $financeiro->parcelas;
                        $procedimento->obs_pagamento = $financeiro->obs_pagamento;
                        $procedimento->data_pagamento = $financeiro->dt_pagamento;
                        $procedimento->vl_pago = $vl_pago;
                        $procedimento->save();
                    }
                }
            }

            if($procedimentos){
                Procedimento::whereIn('id', $procedimentos)->update(['finalizacao_cadastro' => now()]);
                
                if($request->enviar_fila == 1) {
                    // Encontrar apenas o primeiro procedimento do grupo (menor id ou data mais antiga)
                    $primeiro_procedimento = Procedimento::whereIn('id', $procedimentos)
                        ->orderBy('data_aplicacao', 'asc')
                        ->orderBy('id', 'asc')
                        ->first();
                        
                    if($primeiro_procedimento) {
                        $primeiro_procedimento->clinica_id_aplicacao = $user->clinica_id;
                        $primeiro_procedimento->situacao = 'Fila de Aplicação';
                        $primeiro_procedimento->data_aplicacao = date('Y-m-d');
                        $primeiro_procedimento->dt_hr_chegada = date('Y-m-d H:i:s');
                        
                        if(!$primeiro_procedimento->st_biopedancia) $primeiro_procedimento->st_biopedancia = 'Não';
                        if(!$primeiro_procedimento->st_coleta) $primeiro_procedimento->st_coleta = 'Não';
                        if(!$primeiro_procedimento->st_retirada) $primeiro_procedimento->st_retirada = 'Não';
                        
                        $primeiro_procedimento->save();
                    }
                }
            }

            if($controle_request){
                if($request->retorno == 'sistema_dashboard'){
                    return redirect()->route('sistema.dashboard')->with('mensagem','Procedimentos Cadastrados!');
                }
                elseif($request->retorno == 'adm_dashboard'){
                    return redirect()->route('sistema.dashboard')->with('mensagem','Procedimentos Cadastrados!');
                }
                else{
                    return redirect()->route('sistema.procedimentos')->with('mensagem','Procedimentos Cadastrados!');
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro',$e->getMessage());
        }

    }

    public function excluir($id){
        $procedimento = Procedimento::where('id', $id)->first();
        return view('sistema/procedimentos/excluir', compact('procedimento'));
    }

    public function excluir_grupo($codigo){
        return view('sistema/procedimentos/excluir_grupo', compact('codigo'));
    }

    public function delete(Request $request){
        $procedimento = Procedimento::where('id', $request->procedimento_id)->first();

        $this->delete_procedimento($request->procedimento_id);

        FinanceiroSistemaController::atualiza_financeiro_procedimento($procedimento->codigo);

        $this->recalcular_semanas_grupo($procedimento->codigo);

        return redirect()->route('sistema.procedimentos')->with('mensagem','Procedimento Excluído!');
    }

    public function delete_grupo(Request $request){
        $procedimentos = Procedimento::where('codigo', $request->codigo)->get();
        $procedimento = Procedimento::where('codigo', $request->codigo)->first();
        $financeiro = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first();

        foreach($procedimentos as $procedimento){
            $this->delete_procedimento($procedimento->id);
        }

        //$financeiro = Financeiro::where('id', $financeiro_id)->first();
        if($financeiro){
            FinanceiroProcedimento::where('financeiro_id', $financeiro->id)->delete();
            FinanceiroFormasPagamento::where('financeiro_id', $financeiro->id)->delete();
            $financeiro->delete();
        }

        return redirect()->route('sistema.procedimentos')->with('mensagem','Grupo de Procedimentos Excluído!');
    }

    public function delete_procedimento($procedimento_id){
        try {
            $procedimento = Procedimento::where('id', $procedimento_id)->first();
            ProcedimentoAnexo::where('procedimento_id', $procedimento->id)->delete();
            FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->delete();
            foreach($procedimento->aplicacaos as $aplicacao){
                if($aplicacao->situacao == "Aplicada"){
                    if($aplicacao->medicamento->unidade == "Ampola"){
                        AplicacaoLote::where('aplicacao_id', $aplicacao->id)->delete();

                        Estoque::where('origem', 'Procedimento')
                        ->where('procedimento_id', $procedimento->id)
                        ->where('medicamento_id', $aplicacao->medicamento->id)
                        ->delete();
                    }
                    elseif($aplicacao->medicamento->unidade == "Miligrama"){
                        $aplic_lotes = AplicacaoLote::where('aplicacao_id', $aplicacao->id)->get();
                        foreach($aplic_lotes as $lote){
                            $aberto = EstoqueAberto::where('id', $lote->estoque_aberto_id)->first();
                            $aberto->qt_utilizado -= $lote->quantidade;
                            $aberto->qt_restante += $lote->quantidade;
                            if($aberto->qt_restante > 0){
                                $aberto->situacao = 'Aberto';
                            }
                            $aberto->save();
                            $lote->delete();
                        }
                    }
                }

                $aplicacao->delete();
            }

            $procedimento->delete();

        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro',$e->getMessage());
        }

    }

    public function imprimir_paciente($codigo){
        $procedimento = Procedimento::where('codigo', $codigo)->orderBy('nr_procedimento')->first();
        $procedimentos = Procedimento::where('codigo', $codigo)
        ->where('situacao', 'Aplicado')
        ->orderBy('nr_procedimento')
        ->get();

        $array_arquivos = array();

        foreach($procedimentos as $procedimento) {
            $pdf = new GerarPdf('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(true);
            $pdf->SetMargins(10, 40, -1, true);
            $pdf->AddPage();

            //vamos colocar o nome do paciente e a data de cadastro
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(95,6,'Paciente:',0,'L',false,0,'','',true,0,false,true,0,'B',true);
            $pdf->MultiCell(50,6,'CPF:',0,'L',false,0,'','',true,0,false,true,0,'B',true);
            $pdf->MultiCell(0,6,'Data Cadastro:',0,'L',false,1,'','',true,0,false,true,0,'B',true);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->MultiCell(95,5,$procedimento->paciente->nm_paciente,0,'L',false,0,'','',true,0,false,true,0,'M',true);
            $pdf->MultiCell(50,5,$procedimento->paciente->cpf,0,'L',false,0,'','',true,0,false,true,0,'M',true);
            $pdf->MultiCell(0,5,dataDbForm($procedimento->data_cad),0,'L',false,1,'','',true,0,false,true,0,'M',true);

            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(95,6,'Médico:',0,'L',false,0,'','',true,0,false,true,0,'B',true);
            $pdf->MultiCell(0,6,'Clinica:',0,'L',false,1,'','',true,0,false,true,0,'B',true);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->MultiCell(95,5,$procedimento->medico,0,'L',false,0,'','',true,0,false,true,0,'M',true);
            $pdf->MultiCell(0,5,$procedimento->clinica->nome,0,'L',false,1,'','',true,0,false,true,0,'M',true);

            $pdf->SetLineWidth(0.1);
            $pdf->Line(10, 65, 200, 65);
            $pdf->Ln();

            $aplic = $procedimento->aplicacaos()->first();
            if($aplic){
                $ds_aplicacao = $aplic->obs;
            }
            else{
                $ds_aplicacao = '';
            }

            $dt_hr_chegada = '';
            $dt_hr_atendimento = '';
            $dt_hr_finalizacao = '';

            if($procedimento->dt_hr_chegada){
                $var = explode(' ', $procedimento->dt_hr_chegada);
                $dt_hr_chegada = $var[1];
            }

            if($procedimento->dt_hr_atendimento){
                $var = explode(' ', $procedimento->dt_hr_atendimento);
                $dt_hr_atendimento = $var[1];
            }

            if($procedimento->dt_hr_finalizacao){
                $var = explode(' ', $procedimento->dt_hr_finalizacao);
                $dt_hr_finalizacao = $var[1];
            }


            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->MultiCell(160,8,"Semana: ".$procedimento->nr_procedimento,0,'L',false,0,'','',true,0,false,true,0,'B',true);
            $pdf->MultiCell(0,8,dataDbForm($procedimento->data_aplicacao),0,'L',false,1,'','',true,0,false,true,0,'B',true);
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(70,6,'Chegada: '.$dt_hr_chegada,0,'L',false,0,'','',true,0,false,true,0,'M',true);
            $pdf->MultiCell(70,6,'Atendimento: '.$dt_hr_atendimento,0,'L',false,0,'','',true,0,false,true,0,'M',true);
            $pdf->MultiCell(0,6,'Finalização: '.$dt_hr_finalizacao,0,'L',false,1,'','',true,0,false,true,0,'M',true);
            $pdf->Ln(2);
            $pdf->MultiCell(0,6,'OBS:',0,'L',false,1,'','',true,0,false,true,0,'M',true);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0,0,rtrim($ds_aplicacao),0,'L',false,1,'','',true,0,false,true,0,'M',true);

            $tabela = '
            <table style="border-collapse: collapse;width: 100%;border: 1px solid black;">
                <thead>
                    <tr>
                        <th style="border: 1px solid black;"><b>Medicamento</b></th>
                        <th style="border: 1px solid black;"><b>Unidade</b></th>
                        <th style="border: 1px solid black;"><b>Quantidade</b></th>
                        <th style="border: 1px solid black;"><b>Situação</b></th>
                        <th style="border: 1px solid black;"><b>Data Aplicação</b></th>
                        <th style="border: 1px solid black;"><b>Lote Aplicação</b></th>
                        <th style="border: 1px solid black;"><b>C.Barras</b></th>
                        <th style="border: 1px solid black;"><b>Validade</b></th>
                        <th style="border: 1px solid black;"><b>Enfermagem</b></th>
                    </tr>
                </thead>
                <tbody>
                ';
                $enfermeira = null;
                $enfermeira_nome = null;
                foreach($procedimento->aplicacaos as $aplicacao){
                    $dt_aplicacao = null;
                    if($aplicacao->lote){
                        $var = explode(' ',$aplicacao->lote->created_at);
                        $dt_aplicacao = dataDbForm($var[0]);
                    }
                    if(!$enfermeira && $aplicacao->enfermeira){
                        $enfermeira = $aplicacao->enfermeira;
                        $enfermeira_nome = $enfermeira->nome;
                    }
                    $tabela .= '
                        <tr>
                            <td style="border: 1px solid black;">'.$aplicacao->medicamento->nome.'</td>
                            <td style="border: 1px solid black;">'.$aplicacao->medicamento->unidade.'</td>
                            <td style="border: 1px solid black;">'.$aplicacao->quantidade.'</td>
                            <td style="border: 1px solid black;">'.$aplicacao->situacao.'</td>
                            <td style="border: 1px solid black;">'.$dt_aplicacao.'</td>
                            <td style="border: 1px solid black;">'.$aplicacao->lotes().'</td>
                            <td style="border: 1px solid black;">'.$aplicacao->codigos().'</td>
                            <td style="border: 1px solid black;">'.$aplicacao->vencimentos().'</td>
                            <td style="border: 1px solid black;">'.$enfermeira_nome.'</td>
                        </tr>
                    ';
                }

                if($procedimento->st_biopedancia == 'Sim'){
                    $sit = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? 'Aplicada' : 'Aberta';
                    $dt = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? dataDbForm(explode(' ',$procedimento->dt_hr_finalizacao)[0]) : '-';
                    $enf_obj = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? $procedimento->aplicadora : null;
                    $enf = $enf_obj ? $enf_obj->nome : '-';
                    if(!$enfermeira && $enf_obj) $enfermeira = $enf_obj;

                    $tabela .= '
                        <tr>
                            <td style="border: 1px solid black;">Biopedância</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">1</td>
                            <td style="border: 1px solid black;">'.$sit.'</td>
                            <td style="border: 1px solid black;">'.$dt.'</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">'.$enf.'</td>
                        </tr>
                    ';
                }

                if($procedimento->st_coleta == 'Sim'){
                    $sit = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? 'Aplicada' : 'Aberta';
                    $dt = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? dataDbForm(explode(' ',$procedimento->dt_hr_finalizacao)[0]) : '-';
                    $enf_obj = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? $procedimento->aplicadora : null;
                    $enf = $enf_obj ? $enf_obj->nome : '-';
                    if(!$enfermeira && $enf_obj) $enfermeira = $enf_obj;

                    $tabela .= '
                        <tr>
                            <td style="border: 1px solid black;">Coleta ('.$procedimento->tp_coleta.')</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">1</td>
                            <td style="border: 1px solid black;">'.$sit.'</td>
                            <td style="border: 1px solid black;">'.$dt.'</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">'.$enf.'</td>
                        </tr>
                    ';
                }

                if($procedimento->st_retirada == 'Sim'){
                    $sit = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? 'Aplicada' : 'Aberta';
                    $dt = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? dataDbForm(explode(' ',$procedimento->dt_hr_finalizacao)[0]) : '-';
                    $enf_obj = in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? $procedimento->aplicadora : null;
                    $enf = $enf_obj ? $enf_obj->nome : '-';
                    if(!$enfermeira && $enf_obj) $enfermeira = $enf_obj;

                    $tabela .= '
                        <tr>
                            <td style="border: 1px solid black;">Retirada</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">1</td>
                            <td style="border: 1px solid black;">'.$sit.'</td>
                            <td style="border: 1px solid black;">'.$dt.'</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">-</td>
                            <td style="border: 1px solid black;">'.$enf.'</td>
                        </tr>
                    ';
                }

                $tabela .= '
                </tbody>
            </table>
            ';

            $pdf->writeHTML($tabela, true, false, false, false, '');
            $pdf->ln(10);

            if($enfermeira && $enfermeira->imagem_carimbo){
                $pfxPath = public_path("img/usuarios/certificados_digitais/$enfermeira->imagem_carimbo");
                $pfxPass = $enfermeira->senha_certificado;

                $pfxContents = file_get_contents($pfxPath);
                if ($pfxContents === false) {
                    throw new \Exception("Não conseguiu ler o arquivo PFX em: $pfxPath");
                }
                $certs = [];

                if (!openssl_pkcs12_read($pfxContents, $certs, $pfxPass)) {
                    // fallback: informe ao usuário ou converta com openssl CLI
                    throw new \Exception("Falha ao ler o PFX com openssl_pkcs12_read(). Tente converter para PEM via 'openssl pkcs12 -in certificado.pfx -out cert.pem -nodes' e use o PEM.");
                }

                $certPem = $certs['cert'];
                $pkeyPem = $certs['pkey'];

                $pdf->setSignature($certPem, $pkeyPem, '', '', 2);

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $w = 50;
                $h = 25;

                $pdf->setSignatureAppearance($x, $y, $w, $h);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Text($x, $y, "Assinado digitalmente por: $enfermeira->nome");
                $y += 3;
                $pdf->Text($x, $y, "Coren: $enfermeira->coren");
                $y += 3;
                $pdf->Text($x, $y, "Data/Hora: ".date('d/m/Y H:i:s'));
            }

            $arquivo = "Relatório_Procedimentos_$procedimento->id"."_".date('YmdHis').".pdf";
            $diretorio = public_path("procedimentos/$procedimento->id/relatorios");

            if (!File::isDirectory($diretorio)) {
                File::makeDirectory($diretorio, 0755, true, true);
            }

            $destino = $diretorio."/".$arquivo;

            $pdf->Output($destino, 'F');
            $array = [
                'procedimento_id' => $procedimento->id,
                'semana' => $procedimento->nr_procedimento,
                'arquivo' => $arquivo,
                'destino' => $destino,
            ];
            $array_arquivos[] = $array;
        }

        $link_zip = null;

        if(count($array_arquivos) > 0){
            $diretorio_zip = public_path("zips/relatorios");
            if (!File::isDirectory($diretorio_zip)) {
                File::makeDirectory($diretorio_zip, 0755, true, true);
            }

            $path_zip = $diretorio_zip."/$codigo.zip";
            $link_zip = "/public/zips/relatorios/$codigo.zip";
            $zip = new \ZipArchive();

            if (!$zip->open($path_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                throw new \Exception('Falha ao criar arquivo zip.');
            }

            foreach($array_arquivos as $arq){
                $zip->addFile($arq['destino'], $arq['arquivo']);
            }
            $zip->close();
        }

        return view('sistema/procedimentos/imprimir_paciente', compact('array_arquivos','link_zip'));
    }

    public function imprimir_paciente_old($codigo){
        $procedimento = Procedimento::where('codigo', $codigo)->orderBy('nr_procedimento')->first();
        $procedimentos = Procedimento::where('codigo', $codigo)
        ->where('situacao', 'Aplicado')
        ->orderBy('nr_procedimento')
        ->get();
        echo '
        <!doctype html>
        <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Imprimir Procedimentos</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
                <style>
                    @media print {
                        th{
                            font-size: 10px !important
                        }
                        td{
                            font-size: 8px !important
                        }
                    }
                </style>
            </head>
            <body>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-3">
                            <img src="/public/img/logo.png" style="height: 100px">
                        </div>
                        <div class="col-9">
                            <h3 class="text-center mt-5">Relatório de Procedimentos</h3>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-8 form-group">
                            <label>Paciente:</label><br>
                            <strong>'.$procedimento->paciente->nm_paciente.'</strong>
                        </div>
                        <div class="col-4 form-group">
                            <label>Data Cadastro:</label><br>
                            <strong>'.dataDbForm($procedimento->data_cad).'</strong>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 form-group">
                            <label>CPF:</label><br>
                            <strong>'.$procedimento->paciente->cpf.'</strong>
                        </div>
                        <div class="col-4 form-group">
                            <label>Médico:</label><br>
                            <strong>'.$procedimento->medico.'</strong>
                        </div>
                        <div class="col-4 form-group">
                            <label>Clínica:</label><br>
                            <strong>'.$procedimento->clinica->nome.'</strong>
                        </div>
                    </div>
                    <hr>
                    <h5>Procedimentos</h5>
                    ';
                    foreach($procedimentos as $procedimento){
                        $aplic = $procedimento->aplicacaos()->first();
                        if($aplic){
                            $ds_aplicacao = $aplic->obs;
                        }
                        else{
                            $ds_aplicacao = '';
                        }

                        $dt_hr_chegada = '';
                        $dt_hr_atendimento = '';
                        $dt_hr_finalizacao = '';

                        if($procedimento->dt_hr_chegada){
                            $var = explode(' ', $procedimento->dt_hr_chegada);
                            $dt_hr_chegada = dataDbForm($var[0])." ".$var[1];
                        }

                        if($procedimento->dt_hr_atendimento){
                            $var = explode(' ', $procedimento->dt_hr_atendimento);
                            $dt_hr_atendimento = dataDbForm($var[0])." ".$var[1];
                        }

                        if($procedimento->dt_hr_finalizacao){
                            $var = explode(' ', $procedimento->dt_hr_finalizacao);
                            $dt_hr_finalizacao = dataDbForm($var[0])." ".$var[1];
                        }

                        echo '
                        <div class="card mt-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between">
                                    <h6 class="card-title">Semana: '.$procedimento->nr_procedimento.'</h6>
                                    <h6 class="card-title">Data: '.dataDbForm($procedimento->data_aplicacao).'</h6>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>OBS:</label><br>
                                        <strong>'.$ds_aplicacao.'</strong>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label>Chegada:</label><br>
                                        <strong>'.$dt_hr_chegada.'</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Atendimento:</label><br>
                                        <strong>'.$dt_hr_atendimento.'</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Finalização:</label><br>
                                        <strong>'.$dt_hr_finalizacao.'</strong>
                                    </div>
                                </div>
                                <table class="table table-responsive">
                                    <thead>
                                        <tr>
                                            <th>Medicamento</th>
                                            <th>Unidade</th>
                                            <th>Quantidade</th>
                                            <th>Valor</th>
                                            <th>Total</th>
                                            <th>Situação</th>
                                            <th>Data Aplicação</th>
                                            <th>Lote Aplicação</th>
                                            <th>C.Barras</th>
                                            <th>Validade</th>
                                            <th>Enfermagem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    ';
                                    foreach($procedimento->aplicacaos as $aplicacao){
                                        $dt_aplicacao = null;
                                        if($aplicacao->lote){
                                            $var = explode(' ',$aplicacao->lote->created_at);
                                            $dt_aplicacao = dataDbForm($var[0]);
                                        }
                                        $enfermeira = $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '';
                                        $carimbo = $aplicacao->enfermeira->imagem_carimbo;
                                        echo'
                                            <tr>
                                                <td>'.$aplicacao->medicamento->nome.'</td>
                                                <td>'.$aplicacao->medicamento->unidade.'</td>
                                                <td>'.$aplicacao->quantidade.'</td>
                                                <td>R$ '.valorDbForm($aplicacao->valor).'</td>
                                                <td>R$ '.valorDbForm($aplicacao->total).'</td>
                                                <td>'.$aplicacao->situacao.'</td>
                                                <td>'.$dt_aplicacao.'</td>
                                                <td>'.$aplicacao->lotes().'</td>
                                                <td>'.$aplicacao->codigos().'</td>
                                                <td>'.$aplicacao->vencimentos().'</td>
                                                <td>'.$enfermeira.'</td>
                                            </tr>
                                        ';
                                    }
                                    echo '
                                    <tr>
                                        <td colspan="11">
                                            Enfermeiro(a): <img src="/public/img/usuarios/carimbos/'.$carimbo.'" style="height: 100px">
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        ';
                    }
                    echo '
                </div>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
                <script>
                    window.addEventListener("load", ()=>{
                        print();
                    })

                    window.addEventListener("afterprint", ()=>{
                        window.close();
                    })
                </script>
            </body>
        </html>
        ';
    }

    public function imprimir_cadastro($codigo){
        $procedimentos = Procedimento::where('codigo', $codigo)
        ->with(['logs', 'paciente'])
        ->orderBy('nr_procedimento')
        ->get();

        //vamos buscar um resumo das aplicações
        $array_in = array();
        foreach($procedimentos as $procedimento){
            $array_in[] = $procedimento->id;
        }

        $cadastrante = null;
        if($procedimento->cadastrante){
            $cadastrante = $procedimento->cadastrante->nome;
        }

        $medicamentos = Aplicacao::whereIn('procedimento_id', $array_in)
        ->distinct()->pluck('medicamento_id');
        $array_resumo = array();
        foreach($medicamentos as $medicamento_id){
            $med = Medicamento::where('id', $medicamento_id)->first();
            $quantidade = Aplicacao::whereIn('procedimento_id', $array_in)
            ->where('medicamento_id', $medicamento_id)
            ->sum('quantidade');
            $total = Aplicacao::whereIn('procedimento_id', $array_in)
            ->where('medicamento_id', $medicamento_id)
            ->sum('total');

            $array = [
                'medicamento' => $med->nome,
                'quantidade' => $quantidade,
                'valor' => round($total / $quantidade, 2),
                'total' => $total,
            ];

            $array_resumo[] = $array;
        }

        $vl_procedimentos = Procedimento::whereIn('id', $array_in)
        ->sum('valor');

        $vl_pagamentos = Procedimento::whereIn('id', $array_in)
        ->sum('vl_pago');

        //vamos pegar a obs do Pagamento
        $obs_pagamento = '';
        $financeiro = null;
        $financeiro_proc = FinanceiroProcedimento::whereIn('procedimento_id', $array_in)->first();
        if($financeiro_proc){
            $financeiro = Financeiro::where('id', $financeiro_proc->financeiro_id)->first();
            if($financeiro){
                $obs_pagamento = $financeiro->obs_pagamento;
            }
        }

        return view('/sistema/procedimentos/imprimir_cadastro', compact('procedimentos','array_resumo',
        'vl_procedimentos','vl_pagamentos','obs_pagamento','cadastrante','financeiro'));
    }

    public function imprimir(Request $request){
        echo '
        <!doctype html>
        <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Imprimir Procedimento</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
            </head>
            <body>
                <div class="container">
                    '.$request->data.'
                </div>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
            </body>
        </html>
        ';
    }

    public function editar($id){
        $procedimento = Procedimento::where('id', $id)->first();

        $procedimentos_vinculados = Procedimento::where('codigo', $procedimento->codigo)
        ->where('id','<>', $procedimento->id)
        ->orderBy('nr_procedimento')
        ->get();

        $financeiro = null;
        if($procedimento->st_pagamento == 'Sim'){
            $financeiro_id = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first()->financeiro_id;
            $financeiro = Financeiro::where('id', $financeiro_id)->first();
        }
        $medicamentos = Medicamento::all()->sortBy('nome');
        $combos = Combo::all()->sortBy('nome');

        return view('sistema/procedimentos/editar', compact('procedimento','financeiro','procedimentos_vinculados','medicamentos','combos'));
    }

    public function get_aplicacao(){
        $aplicacao = Aplicacao::where('id', $_GET['aplicacao_id'])->first();
        $retorno['medicamento_id'] = $aplicacao->medicamento_id;
        $retorno['quantidade'] = $aplicacao->quantidade;
        $retorno['valor'] = valorDbForm($aplicacao->valor);
        $retorno['total'] = valorDbForm($aplicacao->total);

        echo json_encode($retorno);
    }

    public function update_aplicacao(){
        $aplicacao = Aplicacao::where('id', $_GET['aplicacao_id'])->first();
        $procedimento = Procedimento::where('id', $aplicacao->procedimento_id)->first();
        $procedimento->valor -= $aplicacao->total;


        $aplicacao->medicamento_id = $_GET['medicamento_id'];
        $aplicacao->quantidade = $_GET['quantidade'];
        $aplicacao->valor = valorFormDb($_GET['valor']);
        $aplicacao->total = valorFormDb($_GET['total']);
        $aplicacao->save();

        $procedimento->valor += $aplicacao->total;
        $procedimento->flag_coordenacao = 0;
        $procedimento->flag_qualidade = 0;
        $procedimento->save();

        self::recalcular_situacao($procedimento->id);

        FinanceiroSistemaController::atualiza_financeiro_procedimento($procedimento->codigo);

        $dt_aplicacao = null;
        if($aplicacao->lote){
            $var = explode(' ',$aplicacao->lote->created_at);
            $dt_aplicacao = dataDbForm($var[0]);
        }

        $nome_enfermeira = $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '';

        $html = '
        <td>';
            if($aplicacao->situacao != "Aplicada"){
                $html .= '<div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                        <i class="mdi mdi-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu" data-popper-placement="bottom-end">
                        <button onclick="editar_aplicacao('.$aplicacao->id.')" class="dropdown-item waves-effect"><i class="mdi mdi-pencil me-1"></i> Editar</button>
                        <button onclick="excluir_aplicacao('.$aplicacao->id.')" class="dropdown-item waves-effect"><i class="mdi mdi-delete me-1"></i> Excluir</button>
                    </div>
                </div>';
            }
        $html .= '
        </td>
        <td>'.$aplicacao->medicamento->nome.'</td>
        <td>'.$aplicacao->medicamento->unidade.'</td>
        <td>'.$aplicacao->quantidade.'</td>
        <td>R$ '.valorDbForm($aplicacao->valor).'</td>
        <td>R$ '.valorDbForm($aplicacao->total).'</td>
        <td>'.$aplicacao->obs.'</td>
        <td>'.$aplicacao->situacao.'</td>
        <td>'.$dt_aplicacao.'</td>
        <td>'.$aplicacao->lotes().'</td>
        <td>'.$aplicacao->codigos().'</td>
        <td>'.$nome_enfermeira.'</td>
        ';

        $retorno['html'] = $html;
        echo json_encode($retorno);
    }

    public function delete_aplicacao(){
        $aplicacao = Aplicacao::where('id', $_GET['aplicacao_id'])->first();
        $procedimento = Procedimento::where('id', $aplicacao->procedimento_id)->first();
        $procedimento->valor -= $aplicacao->total;
        $procedimento->flag_coordenacao = 0;
        $procedimento->flag_qualidade = 0;
        $procedimento->save();
        $aplicacao->delete();

        self::recalcular_situacao($procedimento->id);

        FinanceiroSistemaController::atualiza_financeiro_procedimento($procedimento->codigo);

        $retorno['controle'] = 'true';
        echo json_encode($retorno);
    }

    public function insert_aplicacao(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }

        $procedimento = Procedimento::where('id', $_GET['procedimento_id'])->first();
        $medicamento = Medicamento::where('id', $_GET['medicamento_id'])->first();
        $situacao_inicial = 'Aberta';
        $user_id_aplicacao = null;
        if($medicamento && $medicamento->unidade == 'Procedimento') {
            $situacao_inicial = 'Aplicada';
            $user_id_aplicacao = $user->id;
        }

        $dados = [
            'procedimento_id' => $procedimento->id,
            'medicamento_id' => $_GET['medicamento_id'],
            'quantidade' => $_GET['quantidade'],
            'valor' => valorFormDb($_GET['valor']),
            'total' => valorFormDb($_GET['total']),
            'situacao' => $situacao_inicial,
            'user_id_aplicacao' => $user_id_aplicacao,
        ];
        $aplicacao = Aplicacao::create($dados);

        $procedimento->valor += $aplicacao->total;

        if($procedimento->situacao == "Aplicado"){
            $procedimento->situacao = 'Aplicação Parcial';
        }

        // Se o procedimento estava aguardando e agora recebeu uma aplicação, ele está pelo menos parcial
        if($procedimento->situacao == 'Fila de Aplicação' || $procedimento->situacao == 'Pendente' || $procedimento->situacao == 'Agendado'){
            $procedimento->situacao = 'Aplicado';
            $procedimento->data_aplicacao = date('Y-m-d');
        }

        if(empty($procedimento->user_id_aplicacao)){
            $procedimento->user_id_aplicacao = $user->id;
        }

        $procedimento->flag_coordenacao = 0;
        $procedimento->flag_qualidade = 0;
        $procedimento->save();

        self::recalcular_situacao($procedimento->id);

        FinanceiroSistemaController::atualiza_financeiro_procedimento($procedimento->codigo);

        $dt_aplicacao = null;
        if($aplicacao->lote){
            $var = explode(' ',$aplicacao->lote->created_at);
            $dt_aplicacao = dataDbForm($var[0]);
        }

        $nome_enfermeira = $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '';

        $html = '
        <td>';
            if($aplicacao->situacao != "Aplicada"){
                $html .= '<div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                        <i class="mdi mdi-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu" data-popper-placement="bottom-end">
                        <button onclick="editar_aplicacao('.$aplicacao->id.')" class="dropdown-item waves-effect"><i class="mdi mdi-pencil me-1"></i> Editar</button>
                        <button onclick="excluir_aplicacao('.$aplicacao->id.')" class="dropdown-item waves-effect"><i class="mdi mdi-delete me-1"></i> Excluir</button>
                    </div>
                </div>';
            }
        $html .= '
        </td>
        <td>'.$aplicacao->medicamento->nome.'</td>
        <td>'.$aplicacao->medicamento->unidade.'</td>
        <td>'.$aplicacao->quantidade.'</td>
        <td>R$ '.valorDbForm($aplicacao->valor).'</td>
        <td>R$ '.valorDbForm($aplicacao->total).'</td>
        <td>'.$aplicacao->obs.'</td>
        <td>'.$aplicacao->situacao.'</td>
        <td>'.$dt_aplicacao.'</td>
        <td>'.$aplicacao->lotes().'</td>
        <td>'.$aplicacao->codigos().'</td>
        <td>'.$nome_enfermeira.'</td>
        ';
        $retorno['aplicacao_id'] = $aplicacao->id;
        $retorno['html'] = $html;
        echo json_encode($retorno);
    }

    public function insert_combo(Request $request){
        try {
            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            $medicamentos = ComboMedicamento::where('combo_id', $request->combo_id)->get();
            $combo = Combo::find($request->combo_id);
            $is_soro = $combo && str_starts_with(strtolower($combo->nome), 'soro');

            foreach($medicamentos as $linha){
                $dados = [
                    'procedimento_id' => $procedimento->id,
                    'medicamento_id' => $linha->medicamento_id,
                    'quantidade' => $linha->quantidade,
                    'valor' => $linha->valor_unitario,
                    'total' => round($linha->quantidade * $linha->valor_unitario, 2),
                    'situacao' => 'Aberta',
                    'is_soro' => $is_soro,
                ];
                $aplicacao = Aplicacao::create($dados);

                $procedimento->valor += $aplicacao->total;
                $procedimento->flag_coordenacao = 0;
                $procedimento->flag_qualidade = 0;
                $procedimento->save();
            }

            if($procedimento->situacao == "Aplicado"){
                $procedimento->situacao = 'Aplicação Parcial';
                $procedimento->flag_coordenacao = 0;
                $procedimento->flag_qualidade = 0;
                $procedimento->save();
            }

            self::recalcular_situacao($procedimento->id);

            FinanceiroSistemaController::atualiza_financeiro_procedimento($procedimento->codigo);

            return redirect()->route('sistema.procedimentos.editar', $request->procedimento_id)->with('mensagem', 'Combo Adicionado');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos.editar', $request->procedimento_id)->with('mensagem_erro', $e->getMessage());
        }




    }

    public function adicionar_anexos(Request $request){
        try {
            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();

            if($request->hasFile('anexos')){
                foreach($request->file('anexos') as $arquivo){
                    if($arquivo->isValid()){
                        $extensao = $arquivo->extension();
                        $nm_arquivo = str_replace(".$extensao", "", $arquivo->getClientOriginalName());
                        $arquivo_link = $arquivo->getClientOriginalName();
                        $arquivo->move(public_path('procedimentos/'.$procedimento->id."/anexos/"), $arquivo_link);

                        $dados_arq = [
                            'procedimento_id' => $procedimento->id,
                            'nm_anexo' => $nm_arquivo,
                            'anexo' => $arquivo_link,
                        ];

                        ProcedimentoAnexo::create($dados_arq);
                    }
                }

                return redirect()->route('sistema.procedimentos.editar', $procedimento->id);
            }
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro', $e->getMessage());
        }

    }

    public function delete_anexo($id){
        try {
            $anexo = ProcedimentoAnexo::where('id', $id)->first();
            $path = public_path('procedimentos/'.$anexo->procedimento_id."/anexos/".$anexo->anexo);
            if(file_exists($path)){
                unlink($path);
            }
            $anexo->delete();

            return redirect()->back()->with('mensagem','Anexo Excluído!');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro',$e->getMessage());
        }
    }

    public function adicionar_medicamentos($codigo){
        $procedimentos = Procedimento::where('codigo', $codigo)
        ->where('semana_sem_aplicacao','Não')
        ->orderBy('nr_procedimento')->get();
        $medicamentos = Medicamento::all()->sortBy('nome');

        return view('sistema/procedimentos/adicionar_medicamentos', compact('codigo','procedimentos','medicamentos'));
    }

    public function adicionar_medicamentos_insert(Request $request){
        try {
            foreach($request->procedimentos as $procedimento_id){
                $procedimento = Procedimento::where('id', $procedimento_id)->first();

                for($i=0 ; $i<=$request->contador_medicamentos ; $i++){
                    $var = "medicamento_id_".$i;
                    $medicamento_id = $request->$var;

                    $var = "quantidade_".$i;
                    $quantidade = $request->$var;

                    $var = "valor_".$i;
                    $valor = $request->$var;

                    $var = "total_".$i;
                    $total = $request->$var;

                    if($medicamento_id != ""){
                        $var = "is_soro_".$i;
                        $is_soro = $request->$var ?? 0;

                        //entrando aqui vamos adicionar o medicamento a aplicacao
                        $dados = [
                            'procedimento_id' => $procedimento->id,
                            'medicamento_id' => $medicamento_id,
                            'quantidade' => $quantidade,
                            'valor' => valorFormDb($valor),
                            'total' => valorFormDb($total),
                            'situacao' => 'Aberta',
                            'is_soro' => $is_soro,
                        ];

                        Aplicacao::create($dados);
                    }
                }

                $procedimento->valor = Aplicacao::where('procedimento_id', $procedimento->id)->sum('total');
                if($procedimento->situacao == "Aplicado"){
                    $procedimento->situacao = 'Aplicação Parcial';
                }
                $procedimento->flag_coordenacao = 0;
                $procedimento->flag_qualidade = 0;
                $procedimento->save();

                self::recalcular_situacao($procedimento->id);
            }

            FinanceiroSistemaController::atualiza_financeiro_procedimento($request->codigo);

            return redirect()->route('sistema.procedimentos.acessar_grupo', $request->codigo)->with('mensagem', 'Medicamentos Adicionados!');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro', $e->getMessage());
        }

    }

    public function cancelar($codigo){
        return view('sistema/procedimentos/cancelar', compact('codigo'));
    }

    public function cancelar_set(Request $request){
        try {
            $procedimentos = Procedimento::where('codigo', $request->codigo)->get();
            foreach($procedimentos as $procedimento){
                if($procedimento->situacao != "Aplicado"){
                    foreach($procedimento->aplicacaos as $aplicacao){
                        if($aplicacao->situacao != "Aplicada"){
                            $aplicacao->situacao = 'Cancelada';
                            $aplicacao->save();
                        }
                    }
                    $procedimento->situacao = "Cancelado";
                    $procedimento->save();
                }
            }

            return redirect()->route('sistema.procedimentos')->with('mensagem', 'Grupo de Procedimentos Cancelado');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro', $e->getMessage());
        }

    }

    public function recalcular_semanas_grupo($codigo){
        $procedimentos = Procedimento::where('codigo', $codigo)->orderBy('data_aplicacao')->get();
        $i=0;
        foreach($procedimentos as $procedimento){
            $i++;
            $procedimento->nr_procedimento = $i;
            $procedimento->save();
        }
    }

    public function editar_medico($codigo){
        $procedimento = Procedimento::where('codigo', $codigo)->first();
        $api = api();
        $medicos = $api->get_medicos();
        return view('sistema/procedimentos/editar_medico', compact('procedimento','medicos'));
    }

    public function editar_medico_set(Request $request){
        try {
            Procedimento::where('codigo', $request->codigo)->update(['medico' => $request->medico]);
            return redirect()->route('sistema.procedimentos')->with('mensagem', 'Médico Alterado');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function update_flag(Request $request){
        $procedimento = Procedimento::find($request->id);
        if ($procedimento) {
            $flag = $request->flag;
            $value = ($request->value == 1 ? 1 : 0);
            
            $update_data = [$flag => $value];
            $user_nome = "";

            if($value == 1){
                $user = session()->get('user');
                $adm = session()->get('administrador');
                $user_nome = $adm ? $adm->nome : ($user ? $user->nome : "Sistema");

                if($flag == 'flag_coordenacao'){
                    $update_data['user_nome_coordenacao'] = $user_nome;
                }elseif($flag == 'flag_qualidade'){
                    $update_data['user_nome_qualidade'] = $user_nome;
                }
            }else{
                if($flag == 'flag_coordenacao'){
                    $update_data['user_nome_coordenacao'] = null;
                }elseif($flag == 'flag_qualidade'){
                    $update_data['user_nome_qualidade'] = null;
                }
            }

            $procedimento->where('id', $request->id)->update($update_data);
            return response()->json(['success' => true, 'id' => $request->id, 'flag' => $flag, 'value' => $value, 'user_nome' => $user_nome]);
        }
        return response()->json(['success' => false, 'message' => 'Procedimento não encontrado']);
    }

    public function update_data(Request $request){
        try {
            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            $procedimento->data_aplicacao = $request->data_aplicacao;
            $procedimento->save();

            return redirect()->back()->with('mensagem', 'Data do Procedimento Atualizada');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    public function update_google_flag(Request $request){
        $paciente = Paciente::find($request->id);
        if ($paciente) {
            $paciente->st_google = 1;
            $paciente->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Paciente não encontrado']);
    }

    public static function recalcular_situacao($procedimento_id){
        $procedimento = Procedimento::find($procedimento_id);
        if(!$procedimento) return;

        $aplicacoes = Aplicacao::where('procedimento_id', $procedimento_id)->get();
        if($aplicacoes->count() == 0) return;

        $pendentes = 0;
        foreach($aplicacoes as $app){
            if(in_array($app->situacao, ['Aberta', 'Pendente'])){
                $pendentes++;
            }
        }

        if($pendentes == 0 && in_array($procedimento->situacao, ['Aplicação Parcial', 'Atendimento', 'Fila de Aplicação'])){
            $procedimento->situacao = 'Aplicado';
            if(!$procedimento->dt_hr_finalizacao){
                $procedimento->dt_hr_finalizacao = date('Y-m-d H:i:s');
            }
            if(!$procedimento->data_aplicacao){
                $procedimento->data_aplicacao = date('Y-m-d');
            }

            $lastApp = Aplicacao::where('procedimento_id', $procedimento_id)->whereNotNull('user_id_aplicacao')->orderBy('id', 'desc')->first();
            if($lastApp){
                $procedimento->user_id_aplicacao = $lastApp->user_id_aplicacao;
            } elseif(!$procedimento->user_id_aplicacao) {
                $user = auth()->user() ?? session()->get('user');
                if($user){
                    $procedimento->user_id_aplicacao = $user->id;
                }
            }

            $procedimento->save();
        } elseif($pendentes > 0 && $procedimento->situacao == 'Aplicado'){
            $procedimento->situacao = 'Aplicação Parcial';
            $procedimento->save();
        }
    }
}
