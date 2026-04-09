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

    public static function atualiza_financeiro_procedimento($codigo){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }

        //vamos analizar se já possui financeiro esse codigo
        $procedimentos = Procedimento::where('codigo', $codigo)->orderBy('nr_procedimento')->get();
        $financeiro_id = null;
        $controle_financeiro = true;
        $vl_procedimentos = 0;

        foreach($procedimentos as $procedimento){
            $vl_procedimentos += $procedimento->valor;
            if($controle_financeiro){
                $var = FinanceiroProcedimento::where('procedimento_id', $procedimento->id)->first();
                if($var){
                    $financeiro_id = $var->financeiro_id;
                    $controle_financeiro = false;
                }
            }
        }

        if($controle_financeiro){
            $dados = [
                'clinica_id' => $procedimento->clinica_id,
                'paciente_id' => $procedimento->paciente_id,
                'medico' => $procedimento->medico,
                'dt_pagamento' => date('Y-m-d'),
                'vl_consulta' => '0.00',
                'vl_procedimentos' => $vl_procedimentos,
                'vl_desconto' => '0.00',
                'vl_adicional' => '0.00',
                'vl_pagamento' => '0.00',
                'tipo_pagamento' => 'teste',
                'forma_pagamento' => 'teste',
                'parcelas' => '1',
            ];
            $financeiro = Financeiro::create($dados);

            foreach($procedimentos as $procedimento){
                $dados = [
                    'financeiro_id' => $financeiro->id,
                    'procedimento_id' => $procedimento->id,
                ];
                FinanceiroProcedimento::create($dados);
            }
        }
        else{
            $financeiro = Financeiro::where('id', $financeiro_id)->first();
        }

        //vamos recalcular tudo
        $formas = FinanceiroFormasPagamento::where('financeiro_id', $financeiro->id)->orderBy('created_at')->get();
        $valor_pago_formas = $formas->sum('vl_pagamento');

        if($valor_pago_formas > 0){
            if($valor_pago_formas >= $financeiro->vl_consulta){
                $vl_consulta_pagamento = $financeiro->vl_consulta;
            }
            elseif($valor_pago_formas < $financeiro->vl_consulta){
                $vl_consulta_pagamento = $valor_pago_formas;
            }
            $financeiro->vl_consulta_pagamento = $vl_consulta_pagamento;
            $financeiro->save();
        }
        else{
            $financeiro->vl_consulta_pagamento = '0.00';
            $financeiro->save();
        }

        // Para distribuir os pagamentos nos procedimentos com as datas corretas
        // 1. Dinheiro disponível (excedente das formas + desconto - adicional)
        $valor_disponivel = $valor_pago_formas - $financeiro->vl_consulta_pagamento + $financeiro->vl_desconto - $financeiro->vl_adicional;

        // 2. Mapear chunks de dinheiro com datas
        $chunks = [];
        // Desconto entra como dinheiro na data do financeiro
        if($financeiro->vl_desconto > 0){
            $chunks[] = ['valor' => $financeiro->vl_desconto, 'data' => $financeiro->dt_pagamento];
        }

        // Pegar apenas a parte das formas que sobra após pagar a consulta
        $ja_usado_consulta = $financeiro->vl_consulta_pagamento;
        foreach($formas as $forma){
            $valor_forma = $forma->vl_pagamento;
            if($ja_usado_consulta > 0){
                if($valor_forma > $ja_usado_consulta){
                    $valor_forma -= $ja_usado_consulta;
                    $ja_usado_consulta = 0;
                } else {
                    $ja_usado_consulta -= $valor_forma;
                    $valor_forma = 0;
                }
            }
            
            if($valor_forma > 0){
                $chunks[] = ['valor' => $valor_forma, 'data' => date('Y-m-d', strtotime($forma->created_at))];
            }
        }

        // Subtrair adicionais do montante (reduz o dinheiro disponível, começando pelos chunks mais antigos ou apenas subtraindo do total)
        // Por simplicidade, subtraímos do total disponível. No loop abaixo, ele vai parar de pagar quando atingir esse limite.

        $procedimentos = Procedimento::where('codigo', $codigo)
        ->where('st_pagamento','<>','Pendente')
        ->orderBy('nr_procedimento')->get();

        $progresso_pagamento = 0;
        foreach($procedimentos as $procedimento){
            if($valor_disponivel > 0){
                $data_pagamento = $financeiro->dt_pagamento;
                if($valor_disponivel >= $procedimento->valor){
                    $st_pagamento = 'Sim';
                    $vl_pago = $procedimento->valor;
                }
                else {
                    $st_pagamento = 'Parcial';
                    $vl_pago = $valor_disponivel;
                }

                // Encontrar a data do último chunk que contribuiu para este pagamento
                $temp_acumulado = 0;
                $limite_atual = $progresso_pagamento + $vl_pago;
                foreach($chunks as $chunk){
                    $temp_acumulado += $chunk['valor'];
                    if($temp_acumulado >= $limite_atual){
                        $data_pagamento = $chunk['data'];
                        break;
                    }
                    $data_pagamento = $chunk['data']; // Vai pegando a última data se o acumulado ainda não chegou no limite
                }

                $valor_disponivel -= $vl_pago;
                $progresso_pagamento += $vl_pago;

                $procedimento->st_pagamento = $st_pagamento;
                $procedimento->tipo_pagamento = $financeiro->tipo_pagamento;
                $procedimento->forma_pagamento = $financeiro->forma_pagamento;
                $procedimento->parcelas = $financeiro->parcelas;
                $procedimento->obs_pagamento = $financeiro->obs_pagamento;
                $procedimento->data_pagamento = $data_pagamento;
                $procedimento->vl_pago = $vl_pago;
                $procedimento->save();
            }
            else{
                if($procedimento->situacao != "Semana Sem Aplicação"){
                    $procedimento->st_pagamento = 'Não';
                    $procedimento->vl_pago = '0.00';
                    $procedimento->save();
                }
            }
        }
    }

    public function adicionar_pagamento($id){
        $financeiro = Financeiro::where('id', $id)->first();
        return view('sistema/financeiros/adicionar_pagamento', compact('financeiro'));
    }

    public function insert_pagamento(Request $request){
        try {
            $user = auth()->user();
            if(!$user){
                $user = session()->get('user');
            }

            $financeiro = Financeiro::where('id', $request->financeiro_id)->first();

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
                        'user_id_cadastro' => $user->id,
                    ];

                    FinanceiroFormasPagamento::create($dados);
                }
            }
            $financeiro->obs_pagamento = $financeiro->obs_pagamento." / ".$request->obs_pagamento;
            $financeiro->save();

            $this->atualiza_financeiro_procedimento($financeiro->procedimentos()->first()->codigo);

            return redirect()->route('sistema.financeiros.acessar', $financeiro->id)->with('mensagem', 'Pagamentos Lançados');
        } catch (\Exception $e) {
            return redirect()->route('sistema.financeiros')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function delete_pagamento($id = null){
        try {
            $forma = FinanceiroFormasPagamento::where('id', $id)->first();
            $financeiro = Financeiro::where('id', $forma->financeiro_id)->first();
            $forma->delete();

            $this->atualiza_financeiro_procedimento($financeiro->procedimentos()->first()->codigo);

            return redirect()->route('sistema.financeiros.acessar', $financeiro->id)->with('mensagem', 'Pagamento Excluído');
        } catch (\Exception $e) {
            return redirect()->route('sistema.financeiros')->with('mensagem_erro', $e->getMessage());
        }

    }

}
