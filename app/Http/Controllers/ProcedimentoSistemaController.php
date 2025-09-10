<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimento;
use App\Models\ProcedimentoAnexo;
use App\Models\Aplicacao;
use App\Models\Lote;
use App\Models\Medicamento;
use App\Models\Paciente;
use App\Models\Financeiro;
use App\Models\FinanceiroProcedimento;
use App\Models\FinanceiroFormasPagamento;
use App\Models\Administrador;
use Illuminate\Support\Facades\Hash;

class ProcedimentoSistemaController extends Controller
{
    public function index(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $procedimentos = Procedimento::where('nr_procedimento','1')->get();;
        //$pacientes = Paciente::all()->sortBy('nm_paciente');

        return view('sistema/procedimentos/index', compact('procedimentos'));
    }

    public function acessar_grupo($codigo, $retorno = 'null'){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $procedimentos = Procedimento::where('codigo',$codigo)->get();;
        //$pacientes = Paciente::all()->sortBy('nm_paciente');

        return view('sistema/procedimentos/index_grupo', compact('procedimentos','codigo'));
    }

    public function adicionar($retorno = null){
        $api = api();
        $medicos = $api->get_medicos();
        $pacientes = Paciente::all()->sortBy('nm_pacientes');
        $medicamentos = Medicamento::all()->sortBy('nome');
        return view('sistema/procedimentos/adicionar', compact('pacientes','medicamentos','medicos','retorno'));
    }

    public function adicionar_grupo($codigo){
        $api = api();
        $medicos = $api->get_medicos();
        $pacientes = Paciente::all()->sortBy('nm_pacientes');
        $medicamentos = Medicamento::all()->sortBy('nome');
        $retorno = null;
        return view('sistema/procedimentos/adicionar', compact('pacientes','medicamentos','medicos','retorno','codigo'));
    }

    public function insert(Request $request){
        try {
            $array_procedimentos = array();

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
            }
            else{
                $codigo = $request->paciente_id.date('YmdHis');
                $nr_procedimento = 0;
                $medico = $request->medico;
                $paciente_id = $request->paciente_id;
            }

            $paciente = Paciente::where('id', $paciente_id)->first();

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
                        ];
                        $procedimento = Procedimento::create($dados);

                        $array_procedimentos[] = $procedimento;

                        $var = "contador_medicamentos_".$i;
                        $contador = $request->$var;

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

                                $dados = [
                                    'procedimento_id' => $procedimento->id,
                                    'medicamento_id' => $medicamento_id,
                                    'quantidade' => $quantidade,
                                    'valor' => valorFormDb($valor),
                                    'total' => valorFormDb($total),
                                    'situacao' => 'Aberta',
                                ];
                                Aplicacao::create($dados);
                            }
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

            $retorno = $request->retorno;
            $medico = $request->medico;
            return view('sistema/procedimentos/financeiro', compact('array_procedimentos','paciente','retorno','medico'));
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
            $this->financeiro(false, $procedimento);
        }

        $financeiro = Financeiro::where('id', $fin_proc->financeiro_id)->first();

        return view('sistema/procedimentos/acessar', compact('procedimento','retorno','procedimentos_vinculados','controle_aviso_coleta','financeiro'));
    }

    public function setar_pagamento(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            $var = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first();
            $financeiro = Financeiro::where('id', $var->financeiro_id)->first();

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

                if($forma_pagamento && $vl_pagamento){
                    $dados = [
                        'financeiro_id' => $financeiro->id,
                        'forma_pagamento' => $forma_pagamento,
                        'parcelas' => $parcelas,
                        'vl_pagamento' => valorFormDb($vl_pagamento),
                    ];

                    FinanceiroFormasPagamento::create($dados);
                    $valor_pagamento += valorFormDb($vl_pagamento);
                }
            }

            //vamos verificar se a consulta já foi paga
            if($financeiro->vl_consulta > $financeiro->vl_consulta_pagamento){
                $vl_consulta_pendente = $financeiro->vl_consulta - $financeiro->vl_consulta_pagamento;

                if($valor_pagamento >= $vl_consulta_pendente){
                    $financeiro->vl_consulta_pagamento = $financeiro->vl_consulta_pagamento += $vl_consulta_pendente;
                    $valor_pagamento -= $vl_consulta_pendente;
                }
                else{
                    $financeiro->vl_consulta_pagamento = $financeiro->vl_consulta_pagamento += $valor_pagamento;
                    $valor_pagamento -= $valor_pagamento;
                }
                $financeiro->save();
            }

            $procedimentos = Procedimento::where('codigo',$procedimento->codigo)
            ->where('st_pagamento','<>','Sim')
            ->where('nr_procedimento','<',$procedimento->nr_procedimento)
            ->get();

            foreach($procedimentos as $proc){
                if($valor_pagamento > 0){
                    $vl_pendente = $proc->valor - $proc->vl_pago;
                    if($valor_pagamento > $vl_pendente){
                        $proc->vl_pago += $vl_pendente;
                        $valor_pagamento -= $vl_pendente;
                    }
                    else{
                        $proc->vl_pago += $valor_pagamento;
                        $valor_pagamento -= $valor_pagamento;
                    }
                    if($proc->vl_pago < $prov->valor){
                        $proc->st_pagamento = 'Parcial';
                    }
                    else{
                        $proc->st_pagamento = 'Sim';
                    }
                    $proc->save();
                }
            }

            //vamos verificar se sobrou dinheiro para pagar o procedimento em questaão
            if($valor_pagamento > 0){
                $vl_pendente = $procedimento->valor - $procedimento->vl_pago;
                if($valor_pagamento > $vl_pendente){
                    $procedimento->vl_pago += $vl_pendente;
                    $valor_pagamento -= $vl_pendente;
                }
                else{
                    $procedimento->vl_pago += $valor_pagamento;
                    $valor_pagamento -= $valor_pagamento;
                }
                if($procedimento->vl_pago < $procedimento->valor){
                    $procedimento->st_pagamento = 'Parcial';
                }
                else{
                    $procedimento->st_pagamento = 'Sim';
                }
                $procedimento->save();
            }

            return redirect()->route('sistema.procedimentos.acessar', [$procedimento->id, $request->retorno])->with('mensagem','Pagamento Cadastrado');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos.acessar', $procedimento->id)->with('mensagem_erro',$e->getMessage());
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

                if($forma_pagamento && $vl_pagamento){
                    $dados = [
                        'financeiro_id' => $financeiro->id,
                        'forma_pagamento' => $forma_pagamento,
                        'parcelas' => $parcelas,
                        'vl_pagamento' => valorFormDb($vl_pagamento),
                    ];

                    FinanceiroFormasPagamento::create($dados);
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
            $procedimento->consulta_tratamento_agendada = $request->consulta_tratamento_agendada ? $request->consulta_tratamento_agendada : '';
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
            $autorizador = Administrador::where('email',$request->autorizador_email)->first();
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
            $procedimento->autorizador_sem_pagamento = $autorizador->id;
            $procedimento->consulta_tratamento_agendada = $request->consulta_tratamento_agendada ? $request->consulta_tratamento_agendada : '';
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
            if($request){
                $procedimentos = $request->procedimentos ? $request->procedimentos : [];
                $paciente_id = $request->paciente_id
                $medico = $request->medico;
                $vl_consulta = $request->vl_consulta;
                $vl_desconto = $request->vl_desconto;
                $vl_pagamento = $request->vl_pagamento;
                $obs_pagamento = $request->obs_pagamento;
            }
            elseif($procedimento){
                $paciente_id = $procedimento->paciente_id;
                $medico = $procedimento->medico;
                $vl_consulta = '0,00';
                $vl_desconto = '0,00';
                $vl_pagamento = '0,00';
                $obs_pagamento = '';

                $res = Procedimento::where('codigo', $procedimento->codigo)->get();
                $procedimentos = array();
                foreach($res as $linha){
                    $procedimentos[] = $linha->id;
                }
            }
            else{
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
                'vl_pagamento' => valorFormDb($vl_pagamento),
                'tipo_pagamento' => 'teste',
                'forma_pagamento' => 'teste',//$request->forma_pagamento,
                'parcelas' => 1,//$parcelas,
                'obs_pagamento' => $obs_pagamento,
            ];

            $financeiro = Financeiro::create($dados);

            $valor_pago = 0;

            if($request){
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

                    if($forma_pagamento && $vl_pagamento){
                        $dados = [
                            'financeiro_id' => $financeiro->id,
                            'forma_pagamento' => $forma_pagamento,
                            'parcelas' => $parcelas,
                            'vl_pagamento' => valorFormDb($vl_pagamento),
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

            if($request->procedimentos){
                foreach($request->procedimentos as $procedimento_id){
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

            if($request->retorno == 'sistema_dashboard'){
                return redirect()->route('sistema.dashboard')->with('mensagem','Procedimentos Cadastrados!');
            }
            elseif($request->retorno == 'adm_dashboard'){
                return redirect()->route('sistema.dashboard')->with('mensagem','Procedimentos Cadastrados!');
            }
            else{
                return redirect()->route('sistema.procedimentos')->with('mensagem','Procedimentos Cadastrados!');
            }
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro',$e->getMessage());
        }

    }

    public function excluir($id){
        $procedimento = Procedimento::where('id', $id)->first();
        return view('sistema/procedimentos/excluir', compact('procedimento'));
    }

    public function delete(Request $request){
        try {
            $procedimento = Procedimento::where('id', $request->procedimento_id)->first();
            $finProc = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first();
            $finProc->delete();
            $financeiro = Financeiro::where('id', $finProc->financeiro_id)->delete();
            ProcedimentoAnexo::where('procedimento_id', $procedimento->id)->delete();
            Aplicacao::where('procedimento_id', $procedimento->id)->delete();
            $procedimento->delete();

            return redirect()->route('sistema.procedimentos')->with('mensagem','Procedimento Excluído!');
        } catch (\Exception $e) {
            return redirect()->route('sistema.procedimentos')->with('mensagem_erro',$e->getMessage());
        }

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

        return view('sistema/procedimentos/editar', compact('procedimento','financeiro','procedimentos_vinculados','medicamentos'));
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
        $procedimento->save();

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
        $procedimento->save();
        $aplicacao->delete();
        $retorno['controle'] = 'true';
        echo json_encode($retorno);
    }

    public function insert_aplicacao(){
        $procedimento = Procedimento::where('id', $_GET['procedimento_id'])->first();
        $dados = [
            'procedimento_id' => $procedimento->id,
            'medicamento_id' => $_GET['medicamento_id'],
            'quantidade' => $_GET['quantidade'],
            'valor' => valorFormDb($_GET['valor']),
            'total' => valorFormDb($_GET['total']),
            'situacao' => 'Aberta',
        ];
        $aplicacao = Aplicacao::create($dados);

        $procedimento->valor += $aplicacao->total;
        $procedimento->save();

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

}
