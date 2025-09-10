<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Financeiro;
use App\Models\FinanceiroProcedimento;
use App\Models\Procedimento;
use App\Models\FinanceiroFormasPagamento;

class FinanceiroSistemaController extends Controller
{
    public function index(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }
        $financeiros = Financeiro::all();
        return view('sistema/financeiros/index', compact('financeiros'));
    }

    public function adicionar(){
        $api = api();
        $medicos = $api->get_medicos();
        return view('sistema/financeiros/adicionar', compact('medicos'));
    }

    public function get_procedimentos_abertos(){
        $procedimentos = Procedimento::where('paciente_id', $_GET['paciente_id'])
        ->where('st_pagamento',"<>",'Sim')
        ->orderBy('data_aplicacao')
        ->get();

        $html = "";
        foreach($procedimentos as $procedimento){
            $html .= "<option data-valor='$procedimento->valor' value='$procedimento->id'>".dataDbForm($procedimento->data_aplicacao)." - R$".valorDbForm($procedimento->valor)."</option>";
        }
        $retorno['html'] = $html;
        echo json_encode($retorno);
    }

    public function insert(Request $request){
        dd($request);
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

            //vamos gerar o financeiro
            $vl_procedimentos = 0;
            if($request->procedimentos){
                foreach($request->procedimentos as $procedimento_id){
                    $procedimento = Procedimento::where('id', $procedimento_id)->first();
                    $vl_procedimentos += $procedimento->valor;
                }
            }

            $dados = [
                'clinica_id' => $user->clinica_id,
                'paciente_id' => $request->paciente_id,
                'medico' => $request->medico,
                'dt_pagamento' => date('Y-m-d'),
                'vl_consulta' => valorFormDb($request->vl_consulta),
                'vl_procedimentos' => $vl_procedimentos,
                'vl_desconto' => valorFormDb($request->vl_desconto),
                'vl_pagamento' => valorFormDb($request->vl_pagamento),
                'tipo_pagamento' => 'teste',
                'forma_pagamento' => 'teste',//$request->forma_pagamento,
                'parcelas' => 1,//$parcelas,
                'obs_pagamento' => $request->obs_pagamento,
            ];

            $financeiro = Financeiro::create($dados);

            if($request->procedimentos){
                foreach($request->procedimentos as $procedimento_id){
                    $procedimento = Procedimento::where('id', $procedimento_id)->first();

                    $dados = [
                        'financeiro_id' => $financeiro->id,
                        'procedimento_id' => $procedimento->id,
                    ];
                    FinanceiroProcedimento::create($dados);

                    $procedimento->st_pagamento = 'Sim';
                    $procedimento->tipo_pagamento = $financeiro->tipo_pagamento;
                    $procedimento->forma_pagamento = $financeiro->forma_pagamento;
                    $procedimento->parcelas = $financeiro->parcelas;
                    $procedimento->obs_pagamento = $financeiro->obs_pagamento;
                    $procedimento->data_pagamento = $financeiro->dt_pagamento;
                    $procedimento->save();
                }
            }

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

            return redirect()->route('sistema.financeiros')->with('mensagem', 'Financeiro Cadastrado');
        } catch (\Exception $e) {
            return redirect()->route('sistema.financeiros')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function acessar($id){
        $financeiro = Financeiro::where('id', $id)->first();
        return view('sistema/financeiros/acessar', compact('financeiro'));
    }
}
