<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clinica;
use App\Models\Aplicacao;
use App\Models\Procedimento;
use App\Models\User;
use App\Models\Transferencia;
use App\Models\Financeiro;
use App\Models\Medicamento;
use App\Http\Controllers\FinanceiroSistemaController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RelatorioController extends Controller
{
    public function financeiro(){
        $clinicas = Clinica::all()->sortBy('nome');

        //vamos pegar os procedimentos e os medicamentos
        return view('adm/relatorios/financeiro', compact('clinicas'));
    }

    public function vendas(){
        $clinicas = Clinica::all()->sortBy('nome');
        $medicamentos = Medicamento::all()->sortBy('nome');
        $api = api();
        $medicos = $api->get_medicos();

        return view('adm/relatorios/vendas', compact('clinicas','medicamentos','medicos'));
    }

    public function enfermagem(){
        $enfermeiras = User::where('tipo','Enfermagem')->where('st_usuario', 'Ativo')->orderBy('nome')->get();
        $clinicas = Clinica::all()->sortBy('nome');
        return view('adm/relatorios/enfermagem', compact('clinicas','enfermeiras'));
    }

    public function transferencias(){
        return view('adm/relatorios/transferencias');
    }

    public function recepcao(){
        $clinicas = Clinica::all()->sortBy('nome');
        $recepcionistas = User::where('tipo','Secretária')->where('st_usuario', 'Ativo')->orderBy('nome')->get();
        return view('adm/relatorios/recepcao', compact('clinicas','recepcionistas'));
    }

    public function financeiro_gerar(Request $request){
        $dados = $request->except('_token');
        $financeiros = Financeiro::get_pagamentos_relatorio($dados);

        $dt_inc = false;
        $dt_fn = false;

        if($request->dt_inc){
            $dt_inc = $request->dt_inc;
            $dt_inc_stamp = strtotime($dt_inc." 00:00:00");
        }

        if($request->dt_fn){
            $dt_fn = $request->dt_fn;
            $dt_fn_stamp = strtotime($dt_fn." 23:59:59");
        }

        $array_financeiro = array();

        foreach($financeiros as $financeiro){
            foreach($financeiro->formas as $forma){
                $dt_forma_stamp = strtotime($forma->created_at);
                if( (!$dt_inc || $dt_forma_stamp >= $dt_inc_stamp ) && (!$dt_fn || $dt_forma_stamp <= $dt_fn_stamp) ){
                    $procedimento = $financeiro->procedimentos()->first();
                    $rateio_pagamento = $forma->get_rateio_financeiro();
                    $var = explode(" ",$forma->created_at);
                    $data = $var[0];
                    $contador = $procedimento ? Procedimento::where('codigo', $procedimento->codigo)->count() : '0';
                    $codigo = $procedimento ? $procedimento->codigo : '';

                    if($rateio_pagamento['vl_consulta'] > 0){
                        $linha_dados = [
                            'financeiro_id' => $forma->financeiro_id,
                            'pagamento_id' => $forma->id,
                            'ordem' => strtotime($data),
                            'data' => dataDbForm($data),
                            'paciente' => $financeiro->paciente->nm_paciente,
                            'paciente_id' => $financeiro->paciente->id,
                            'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                            'cpf' => $financeiro->paciente->cpf,
                            'codigo' => $codigo,
                            'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                            'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                            'vl_rateio' => 'R$ '.valorDbForm($rateio_pagamento['vl_consulta']),
                            'tp_pagamento' => 'Consulta',
                            'tipo_atendimento' => $procedimento ? $procedimento->tipo_atendimento : '',
                            'desconto' => valorDbForm(0.00),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'id_pagamento' => $forma->id_pagamento,
                            'parcelas' => $forma->parcelas,
                            'obs' => $financeiro->obs_pagamento,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                        ];
                        $array_financeiro[] = $linha_dados;
                    }

                    if($rateio_pagamento['vl_aplicacao'] > 0){
                        $linha_dados = [
                            'financeiro_id' => $forma->financeiro_id,
                            'pagamento_id' => $forma->id,
                            'ordem' => strtotime($data),
                            'data' => dataDbForm($data),
                            'paciente' => $financeiro->paciente->nm_paciente,
                            'paciente_id' => $financeiro->paciente->id,
                            'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                            'cpf' => $financeiro->paciente->cpf,
                            'codigo' => $codigo,
                            'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                            'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                            'vl_rateio' => 'R$ '.valorDbForm($rateio_pagamento['vl_aplicacao']),
                            'tp_pagamento' => 'Aplicação',
                            'tipo_atendimento' => $procedimento ? $procedimento->tipo_atendimento : '',
                            'desconto' => valorDbForm($financeiro->vl_desconto),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'id_pagamento' => $forma->id_pagamento,
                            'parcelas' => $forma->parcelas,
                            'obs' => $financeiro->obs_pagamento,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                        ];
                        $array_financeiro[] = $linha_dados;
                    }

                    if(isset($rateio_pagamento['detalhes_procedimentos']) && count($rateio_pagamento['detalhes_procedimentos']) > 0){
                        foreach($rateio_pagamento['detalhes_procedimentos'] as $dp){
                            if($dp['valor'] > 0){
                                $linha_dados = [
                                    'financeiro_id' => $forma->financeiro_id,
                                    'pagamento_id' => $forma->id,
                                    'ordem' => strtotime($data),
                                    'data' => dataDbForm($data),
                                    'paciente' => $financeiro->paciente->nm_paciente,
                                    'paciente_id' => $financeiro->paciente->id,
                                    'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                                    'cpf' => $financeiro->paciente->cpf,
                                    'codigo' => $codigo,
                                    'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                                    'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                                    'vl_rateio' => 'R$ '.valorDbForm($dp['valor']),
                                    'tp_pagamento' => 'Procedimento (' . $dp['nome'] . ')',
                                    'tipo_atendimento' => $procedimento ? $procedimento->tipo_atendimento : '',
                                    'desconto' => valorDbForm($financeiro->vl_desconto),
                                    'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                                    'forma_pagamento' => $forma->forma_pagamento,
                                    'id_pagamento' => $forma->id_pagamento,
                                    'parcelas' => $forma->parcelas,
                                    'obs' => $financeiro->obs_pagamento,
                                    'clinica' => $financeiro->clinica->nome,
                                    'medico' => $financeiro->medico,
                                    'contador' => $contador,
                                ];
                                $array_financeiro[] = $linha_dados;
                            }
                        }
                    } elseif(isset($rateio_pagamento['vl_procedimento']) && $rateio_pagamento['vl_procedimento'] > 0){
                        $linha_dados = [
                            'financeiro_id' => $forma->financeiro_id,
                            'pagamento_id' => $forma->id,
                            'ordem' => strtotime($data),
                            'data' => dataDbForm($data),
                            'paciente' => $financeiro->paciente->nm_paciente,
                            'paciente_id' => $financeiro->paciente->id,
                            'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                            'cpf' => $financeiro->paciente->cpf,
                            'codigo' => $codigo,
                            'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                            'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                            'vl_rateio' => 'R$ '.valorDbForm($rateio_pagamento['vl_procedimento']),
                            'tp_pagamento' => 'Procedimento',
                            'tipo_atendimento' => $procedimento ? $procedimento->tipo_atendimento : '',
                            'desconto' => valorDbForm($financeiro->vl_desconto),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'id_pagamento' => $forma->id_pagamento,
                            'parcelas' => $forma->parcelas,
                            'obs' => $financeiro->obs_pagamento,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                        ];
                        $array_financeiro[] = $linha_dados;
                    }
                }
            }
        }

        //vamos organizar o array
        usort($array_financeiro, function($a, $b) {
            return $a['ordem'] <=> $b['ordem'];
        });

        return view('adm/relatorios/financeiro_gerar', compact('array_financeiro', 'dados'));
    }

    public function financeiro_gerar_old(Request $request){
        $dados = $request->except('_token');
        $financeiros = Financeiro::get_pagamentos_relatorio($dados);
        $dt_inc = false;
        $dt_fn = false;

        if($request->dt_inc){
            $dt_inc = $request->dt_inc;
        }

        if($request->dt_fn){
            $dt_fn = $request->dt_fn;
        }

        $array_financeiro = array();

        foreach($financeiros as $financeiro){
            foreach($financeiro->formas as $forma){
                if( (!$dt_inc || strtotime($forma->created_at) >= strtotime($dt_inc." 00:00:00") ) && (!$dt_fn || strtotime($forma->created_at <= strtotime($dt_fn.' 00:00:00'))) ){

                    $procedimento = $financeiro->procedimentos()->first();
                    $rateio_pagamento = $forma->get_rateio_financeiro();
                    $var = explode(" ",$forma->created_at);
                    $data = $var[0];
                    $contador = $procedimento ? Procedimento::where('codigo', $procedimento->codigo)->count() : '0';
                    $codigo = $procedimento ? $procedimento->codigo : '';

                    $dados = [
                        'ordem' => strtotime($data),
                        'data' => dataDbForm($data),
                        'paciente' => $financeiro->paciente->nm_paciente,
                        'cpf' => $financeiro->paciente->cpf,
                        'codigo' => $codigo,
                        'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                        'vl_consulta' => 'R$ '.valorDbForm($rateio_pagamento['vl_consulta']),
                        'vl_aplicacao' => 'R$ '.valorDbForm($rateio_pagamento['vl_aplicacao']),
                        'forma_pagamento' => $forma->forma_pagamento,
                        'id_pagamento' => $forma->id_pagamento,
                        'parcelas' => $forma->parcelas,
                        'clinica' => $financeiro->clinica->nome,
                        'medico' => $financeiro->medico,
                        'contador' => $contador,
                        'tipo_consulta' => $rateio_pagamento['tipo_consulta'],
                        'tipo_aplicacao' => $rateio_pagamento['tipo_aplicacao'],
                    ];

                    $array_financeiro[] = $dados;
                }
            }
        }

        //vamos organizar o array
        usort($array_financeiro, function($a, $b) {
            return $a['ordem'] <=> $b['ordem'];
        });

        return view('adm/relatorios/financeiro_gerar', compact('array_financeiro'));
    }

    public function vendas_gerar(Request $request){
        $dados = $request->except('_token');
        $procedimentos = Procedimento::gerar_relatorio_vendas($dados);

        // Forçar atualização das datas e status financeiros para corrigir dados antigos
        $codigos = $procedimentos->pluck('codigo')->unique();
        foreach($codigos as $codigo){
            FinanceiroSistemaController::atualiza_financeiro_procedimento($codigo);
        }

        // Recarregar para garantir que pegamos as datas corrigidas
        $procedimentos = Procedimento::gerar_relatorio_vendas($dados);

        $medicamento_id = $dados['medicamento_id'];
        $situacao = $dados['situacao'];

        return view('adm/relatorios/vendas_gerar', compact('procedimentos','medicamento_id','situacao','dados'));
    }

    public function enfermagem_gerar(Request $request){
        $dados = $request->except('_token');
        $procedimentos = Procedimento::gerar_relatorio_enfermagem($dados);

        return view('adm/relatorios/enfermagem_gerar', compact('procedimentos','dados'));
    }

    public function transferencias_gerar(Request $request){
        $dados = $request->except('_token');
        $transferencias = Transferencia::gerar_relatorio_transferencias($dados);

        return view('adm/relatorios/transferencias_gerar', compact('transferencias','dados'));
    }

    public function exportar(Request $request){
        $arquivo = "Exportar Relatorio - ".date('d.m.Y - H:i').'.xls';
        $arquivo = str_replace(":",'h',$arquivo);

        // Configurações header para forçar o download
        header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header ("Cache-Control: no-cache, must-revalidate");
        header ("Pragma: no-cache");
        header ("Content-type: application/x-msexcel");
        header ("Content-Disposition: attachment; filename=\"{$arquivo}\"" );
        header ("Content-Description: PHP Generated Data" );
        // Envia o conteúdo do arquivo
        echo $request->data;
        exit();
    }

    public function exportar_enfermagem(Request $request){
        $dados = json_decode($request->dados, true);

        $procedimentos = Procedimento::gerar_relatorio_enfermagem($dados);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $array_dados = [
            'Chegada',
            'Atendimento',
            'Tipo',
            'Finalização',
            'Aplicação',
            'Paciente',
            'Enfermeira',
            'Médico',
            'Medicamento',
            'Quantidade',
            'Unitário',
            'Valor',
            'Lote',
            'C. Barras',
            'Validade',
            'Obs',
            'Procedimento',
            'Pagamento',
            'Coord.',
            'Qual.'
        ];

        $linhaAtual = 1;
        $sheet->fromArray($array_dados, null, 'A' . $linhaAtual);

        foreach($procedimentos as $procedimento){

            $chegada = "";
            $atendimento = "";
            $finalizacao = "";

            if($procedimento->dt_hr_chegada){
                $var = explode(' ',$procedimento->dt_hr_chegada);
                $chegada = dataDbForm($var[0])." ".$var[1];
            } elseif ($procedimento->inicio_cadastro) {
                $var = explode(' ',$procedimento->inicio_cadastro);
                $chegada = dataDbForm($var[0])." ".($var[1] ?? '00:00:00');
            }

            if($procedimento->dt_hr_atendimento){
                $var = explode(' ',$procedimento->dt_hr_atendimento);
                $atendimento = dataDbForm($var[0])." ".$var[1];
            }

            if($procedimento->dt_hr_finalizacao){
                $var = explode(' ',$procedimento->dt_hr_finalizacao);
                $finalizacao = dataDbForm($var[0])." ".$var[1];
            }

            foreach($procedimento->aplicacaos as $aplicacao){
                if($aplicacao->situacao == 'Aplicada'){
                    $var = explode(' ', $aplicacao->updated_at);
                    $data_aplicada = $var[0];
                    $data = dataDbForm($data_aplicada);
                    $hora = $var[1];

                    // Filtrar por intervalo de datas se estiver definido nos dados de busca
                    $exibir = true;
                    if(isset($dados['dt_inc']) && $dados['dt_inc'] && $data_aplicada < $dados['dt_inc']){
                        $exibir = false;
                    }
                    if(isset($dados['dt_fn']) && $dados['dt_fn'] && $data_aplicada > $dados['dt_fn']){
                        $exibir = false;
                    }

                    if($exibir){
                        // Obter a data do procedimento e a data da aplicação
                        $data_aplicada_date = date('Y-m-d', strtotime($aplicacao->updated_at));
                        $procedimento_date = $procedimento->data_aplicacao;

                        if ($aplicacao->dt_hr_chegada) {
                            $app_chegada = $aplicacao->dt_hr_chegada;
                        } else {
                            if ($data_aplicada_date === $procedimento_date) {
                                $app_chegada = $procedimento->dt_hr_chegada ?? $aplicacao->updated_at;
                            } else {
                                $app_chegada = $aplicacao->updated_at;
                            }
                        }

                        if ($aplicacao->dt_hr_atendimento) {
                            $app_atendimento = $aplicacao->dt_hr_atendimento;
                        } else {
                            if ($data_aplicada_date === $procedimento_date) {
                                $app_atendimento = $procedimento->dt_hr_atendimento ?? $aplicacao->updated_at;
                            } else {
                                $app_atendimento = $aplicacao->updated_at;
                            }
                        }

                        $chegada_val = "";
                        if($app_chegada){
                            $var_c = explode(' ', $app_chegada);
                            $chegada_val = dataDbForm($var_c[0])." ".($var_c[1] ?? '00:00:00');
                        }

                        $atendimento_val = "";
                        if($app_atendimento){
                            $var_a = explode(' ', $app_atendimento);
                            $atendimento_val = dataDbForm($var_a[0])." ".($var_a[1] ?? '00:00:00');
                        }

                        $linhaAtual++;
                        $array_dados = [
                            $chegada_val,
                            $atendimento_val,
                            $procedimento->tipo_atendimento,
                            $finalizacao,
                            $data . " " . $hora,
                            $procedimento->paciente->nm_paciente,
                            $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '',
                            $procedimento->medico,
                            $aplicacao->medicamento->nome,
                            $aplicacao->quantidade,
                            'R$ '.valorDbForm($aplicacao->valor),
                            'R$ '.valorDbForm($aplicacao->total),
                            $aplicacao->lotes(),
                            $aplicacao->codigos(),
                            $aplicacao->vencimentos(),
                            $aplicacao->obs,
                            $procedimento->codigo . '/' . $procedimento->nr_procedimento,
                            $procedimento->st_pagamento,
                            $procedimento->flag_coordenacao == 1 ? 'Sim' : 'Não',
                            $procedimento->flag_qualidade == 1 ? 'Sim' : 'Não'
                        ];
                        $sheet->fromArray($array_dados, null, 'A' . $linhaAtual);
                    }
                }
            }
        }

        // Caminho onde o arquivo será salvo
        $arq = "Enfermagem_".date('YmdHis');
        $path = public_path('rel_enfermagem/'.$arq.'.xlsx');
        if(!is_dir(public_path('rel_enfermagem'))){
            mkdir(public_path('rel_enfermagem'), 0755, true);
        }

        // Salva o arquivo
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }

    public function exportar_financeiro(Request $request){
        $dados_req = json_decode($request->dados, true);
        $financeiros = Financeiro::get_pagamentos_relatorio($dados_req);

        $dt_inc = false;
        $dt_fn = false;

        if(isset($dados_req['dt_inc']) && $dados_req['dt_inc']){
            $dt_inc = $dados_req['dt_inc'];
            $dt_inc_stamp = strtotime($dt_inc." 00:00:00");
        }

        if(isset($dados_req['dt_fn']) && $dados_req['dt_fn']){
            $dt_fn = $dados_req['dt_fn'];
            $dt_fn_stamp = strtotime($dt_fn." 23:59:59");
        }

        $array_financeiro = array();

        foreach($financeiros as $financeiro){
            foreach($financeiro->formas as $forma){
                $dt_forma_stamp = strtotime($forma->created_at);
                if( (!$dt_inc || $dt_forma_stamp >= $dt_inc_stamp ) && (!$dt_fn || $dt_forma_stamp <= $dt_fn_stamp) ){
                    $procedimento = $financeiro->procedimentos()->first();
                    $rateio_pagamento = $forma->get_rateio_financeiro();
                    $var = explode(" ",$forma->created_at);
                    $data = $var[0];
                    $contador = $procedimento ? Procedimento::where('codigo', $procedimento->codigo)->count() : '0';
                    $codigo = $procedimento ? $procedimento->codigo : '';

                    if($rateio_pagamento['vl_consulta'] > 0){
                        $dados = [
                            'pagamento_id' => $forma->id,
                            'ordem' => strtotime($data),
                            'data' => dataDbForm($data),
                            'paciente' => $financeiro->paciente->nm_paciente,
                            'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                            'cpf' => $financeiro->paciente->cpf,
                            'codigo' => $codigo,
                            'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                            'vl_rateio' => 'R$ '.valorDbForm($rateio_pagamento['vl_consulta']),
                            'tp_pagamento' => 'Consulta',
                            'desconto' => valorDbForm(0.00),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'id_pagamento' => $forma->id_pagamento,
                            'parcelas' => $forma->parcelas,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                            'obs' => $financeiro->obs_pagamento,
                        ];
                        $array_financeiro[] = $dados;
                    }

                    if($rateio_pagamento['vl_aplicacao'] > 0){
                        $dados = [
                            'pagamento_id' => $forma->id,
                            'ordem' => strtotime($data),
                            'data' => dataDbForm($data),
                            'paciente' => $financeiro->paciente->nm_paciente,
                            'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                            'cpf' => $financeiro->paciente->cpf,
                            'codigo' => $codigo,
                            'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                            'vl_rateio' => 'R$ '.valorDbForm($rateio_pagamento['vl_aplicacao']),
                            'tp_pagamento' => 'Aplicação',
                            'desconto' => valorDbForm($financeiro->vl_desconto),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'id_pagamento' => $forma->id_pagamento,
                            'parcelas' => $forma->parcelas,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                            'obs' => $financeiro->obs_pagamento,
                        ];
                        $array_financeiro[] = $dados;
                    }

                    if(isset($rateio_pagamento['detalhes_procedimentos']) && count($rateio_pagamento['detalhes_procedimentos']) > 0){
                        foreach($rateio_pagamento['detalhes_procedimentos'] as $dp){
                            if($dp['valor'] > 0){
                                $dados = [
                                    'pagamento_id' => $forma->id,
                                    'ordem' => strtotime($data),
                                    'data' => dataDbForm($data),
                                    'paciente' => $financeiro->paciente->nm_paciente,
                                    'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                                    'cpf' => $financeiro->paciente->cpf,
                                    'codigo' => $codigo,
                                    'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                                    'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                                    'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                                    'vl_rateio' => 'R$ '.valorDbForm($dp['valor']),
                                    'tp_pagamento' => 'Procedimento (' . $dp['nome'] . ')',
                                    'desconto' => valorDbForm($financeiro->vl_desconto),
                                    'forma_pagamento' => $forma->forma_pagamento,
                                    'id_pagamento' => $forma->id_pagamento,
                                    'parcelas' => $forma->parcelas,
                                    'clinica' => $financeiro->clinica->nome,
                                    'medico' => $financeiro->medico,
                                    'contador' => $contador,
                                    'obs' => $financeiro->obs_pagamento,
                                ];
                                $array_financeiro[] = $dados;
                            }
                        }
                    } elseif(isset($rateio_pagamento['vl_procedimento']) && $rateio_pagamento['vl_procedimento'] > 0){
                        $dados = [
                            'pagamento_id' => $forma->id,
                            'ordem' => strtotime($data),
                            'data' => dataDbForm($data),
                            'paciente' => $financeiro->paciente->nm_paciente,
                            'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                            'cpf' => $financeiro->paciente->cpf,
                            'codigo' => $codigo,
                            'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                            'vl_rateio' => 'R$ '.valorDbForm($rateio_pagamento['vl_procedimento']),
                            'tp_pagamento' => 'Procedimento',
                            'desconto' => valorDbForm($financeiro->vl_desconto),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'id_pagamento' => $forma->id_pagamento,
                            'parcelas' => $forma->parcelas,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                            'obs' => $financeiro->obs_pagamento,
                        ];
                        $array_financeiro[] = $dados;
                    }
                }
            }
        }

        //vamos organizar o array
        usort($array_financeiro, function($a, $b) {
            return $a['ordem'] <=> $b['ordem'];
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $cabecalho = [
            'ID', 'Data', 'Paciente', 'ID Feegow', 'CPF', 'Codigo', 'Valor Tratamento', 'Desconto Total',
            'Pagamento', 'Valor Rateio', 'Tipo', 'Desconto Rateio', 'Forma Pagamento', 'ID Pagamento', 'Parcelas',
            'Clinica', 'Médico', 'Nr Procedimentos', 'Obs'
        ];

        $sheet->fromArray($cabecalho, null, 'A1');

        $linhaTotal = 2;
        foreach($array_financeiro as $linha){
            $array_excel = [
                $linha['pagamento_id'],
                $linha['data'],
                $linha['paciente'],
                $linha['id_feegow'],
                $linha['cpf'],
                $linha['codigo'],
                $linha['vl_tratamento'],
                $linha['desconto_total'],
                $linha['vl_pagamento'],
                $linha['vl_rateio'],
                $linha['tp_pagamento'],
                $linha['desconto'],
                $linha['forma_pagamento'],
                $linha['id_pagamento'],
                $linha['parcelas'],
                $linha['clinica'],
                $linha['medico'],
                $linha['contador'],
                $linha['obs']
            ];
            $sheet->fromArray($array_excel, null, 'A' . $linhaTotal);
            $linhaTotal++;
        }

        $arq = "Financeiro_".date('YmdHis');
        $path = public_path('rel_financeiro/'.$arq.'.xlsx');
        if(!is_dir(public_path('rel_financeiro'))){
            mkdir(public_path('rel_financeiro'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }

    public function exportar_vendas(Request $request){
        $dados_req = json_decode($request->dados, true);
        $procedimentos = Procedimento::gerar_relatorio_vendas($dados_req);

        // Forçar atualização das datas e status financeiros para corrigir dados antigos na exportação
        $codigos = $procedimentos->pluck('codigo')->unique();
        foreach($codigos as $codigo){
            FinanceiroSistemaController::atualiza_financeiro_procedimento($codigo);
        }

        // Recarregar para garantir que exportamos as datas corrigidas
        $procedimentos = Procedimento::gerar_relatorio_vendas($dados_req);

        $medicamento_id = isset($dados_req['medicamento_id']) ? $dados_req['medicamento_id'] : null;
        $situacao = isset($dados_req['situacao']) ? $dados_req['situacao'] : null;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $cabecalho = [
            'Medicamento', 'Quantidada', 'Status', 'Cadastro', 'Aplicação', 'Valor', 'Pago', 'Data Pagamento', 'Procedimento', 'Paciente', 'Médico'
        ];

        $sheet->fromArray($cabecalho, null, 'A1');

        $linhaTotal = 2;
        foreach($procedimentos as $procedimento){
            foreach($procedimento->aplicacaos as $aplicacao){
                if((!$medicamento_id || $aplicacao->medicamento->id == $medicamento_id) && (!$situacao || $situacao == $aplicacao->situacao)){
                    $array_excel = [
                        $aplicacao->medicamento->nome,
                        $aplicacao->quantidade,
                        $aplicacao->situacao,
                        dataDbForm($procedimento->data_cad),
                        dataDbForm($procedimento->data_aplicacao),
                        'R$ '.valorDbForm($aplicacao->total),
                        $procedimento->st_pagamento,
                        ($procedimento->st_pagamento == 'Sim' || $procedimento->st_pagamento == 'Parcial') ? dataDbForm($procedimento->data_pagamento) : '',
                        $procedimento->codigo."/".$procedimento->nr_procedimento,
                        $procedimento->paciente->nm_paciente,
                        $procedimento->medico
                    ];
                    $sheet->fromArray($array_excel, null, 'A' . $linhaTotal);
                    $linhaTotal++;
                }
            }
        }

        $arq = "Vendas_".date('YmdHis');
        $path = public_path('rel_vendas/'.$arq.'.xlsx');
        if(!is_dir(public_path('rel_vendas'))){
            mkdir(public_path('rel_vendas'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }

    public function exportar_transferencias(Request $request){
        $dados_req = json_decode($request->dados, true);
        $transferencias = Transferencia::gerar_relatorio_transferencias($dados_req);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $cabecalho = [
            'Data', 'Origem', 'Destino', 'Usuário', 'Medicamento', 'Lote', 'C. Barras', 'Quantidade'
        ];

        $sheet->fromArray($cabecalho, null, 'A1');

        $linhaTotal = 2;
        foreach($transferencias as $transferencia){
            $medicamentos = \App\Models\Estoque::where('origem','Transferencia')
            ->where('transferencia_id', $transferencia->id)
            ->where('tipo', 'Saida')
            ->get();

            foreach($medicamentos as $estoque){
                $array_excel = [
                    dataDbForm($transferencia->data),
                    $transferencia->origem->nome,
                    $transferencia->destino->nome,
                    $transferencia->user ? $transferencia->user->name : '',
                    $estoque->medicamento->nome,
                    $estoque->lote,
                    $estoque->codigo_barras,
                    $estoque->quantidade
                ];
                $sheet->fromArray($array_excel, null, 'A' . $linhaTotal);
                $linhaTotal++;
            }
        }

        $arq = "Transferencias_".date('YmdHis');
        $path = public_path('rel_transferencias/'.$arq.'.xlsx');
        if(!is_dir(public_path('rel_transferencias'))){
            mkdir(public_path('rel_transferencias'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }

    public function baixas(){
        $clinicas = Clinica::all()->sortBy('nome');
        $medicamentos = Medicamento::all()->sortBy('nome');
        return view('adm/relatorios/baixas', compact('clinicas','medicamentos'));
    }

    public function baixas_gerar(Request $request){
        $dados = $request->except('_token');
        
        // 1. Baixas de Fechados (Estoque com origem 'Baixa')
        $queryFechados = \App\Models\Estoque::where('origem', 'Baixa')->where('tipo', 'Saida');
        
        if($request->dt_inc){
            $queryFechados->where('created_at', '>=', $request->dt_inc . " 00:00:00");
        }
        if($request->dt_fn){
            $queryFechados->where('created_at', '<=', $request->dt_fn . " 23:59:59");
        }
        if($request->clinica_id){
            $queryFechados->where('clinica_id', $request->clinica_id);
        }
        if($request->medicamento_id){
            $queryFechados->where('medicamento_id', $request->medicamento_id);
        }
        
        $fechados = $queryFechados->with(['medicamento', 'clinica', 'baixa.user'])->get();

        // 2. Baixas de Abertos (BaixaAberto)
        $queryAbertos = \App\Models\BaixaAberto::query();
        if($request->dt_inc){
            $queryAbertos->where('created_at', '>=', $request->dt_inc . " 00:00:00");
        }
        if($request->dt_fn){
            $queryAbertos->where('created_at', '<=', $request->dt_fn . " 23:59:59");
        }
        if($request->clinica_id){
            $queryAbertos->where('clinica_id', $request->clinica_id);
        }
        if($request->medicamento_id){
            $queryAbertos->whereHas('estoque', function($q) use ($request){
                $q->where('medicamento_id', $request->medicamento_id);
            });
        }
        
        $abertos = $queryAbertos->with(['estoque.medicamento', 'clinica', 'user'])->get();

        $movimentacoes = array();
        
        foreach($fechados as $item){
            $movimentacoes[] = [
                'data' => $item->created_at,
                'clinica' => $item->clinica->nome ?? 'N/A',
                'medicamento' => $item->medicamento->nome ?? 'N/A',
                'lote' => $item->lote,
                'quantidade' => $item->quantidade,
                'tipo' => 'Fechado',
                'motivo' => $item->baixa->motivo ?? 'N/A',
                'usuario' => $item->baixa->user->nome ?? 'N/A'
            ];
        }

        foreach($abertos as $item){
            $movimentacoes[] = [
                'data' => $item->created_at,
                'clinica' => $item->clinica->nome ?? 'N/A',
                'medicamento' => $item->estoque->medicamento->nome ?? 'N/A',
                'lote' => $item->estoque->lote ?? 'N/A',
                'quantidade' => $item->quantidade,
                'tipo' => 'Aberto',
                'motivo' => $item->motivo,
                'usuario' => $item->user->nome ?? 'N/A'
            ];
        }

        usort($movimentacoes, function($a, $b) {
            return $b['data'] <=> $a['data'];
        });

        return view('adm/relatorios/baixas_gerar', compact('movimentacoes', 'dados'));
    }

    public function exportar_baixas(Request $request){
        $dados = json_decode($request->dados, true);
        
        $queryFechados = \App\Models\Estoque::where('origem', 'Baixa')->where('tipo', 'Saida');
        
        if(isset($dados['dt_inc'])){
            $queryFechados->where('created_at', '>=', $dados['dt_inc'] . " 00:00:00");
        }
        if(isset($dados['dt_fn'])){
            $queryFechados->where('created_at', '<=', $dados['dt_fn'] . " 23:59:59");
        }
        if(isset($dados['clinica_id'])){
            $queryFechados->where('clinica_id', $dados['clinica_id']);
        }
        if(isset($dados['medicamento_id'])){
            $queryFechados->where('medicamento_id', $dados['medicamento_id']);
        }
        
        $fechados = $queryFechados->with(['medicamento', 'clinica', 'baixa.user'])->get();

        $queryAbertos = \App\Models\BaixaAberto::query();
        if(isset($dados['dt_inc'])){
            $queryAbertos->where('created_at', '>=', $dados['dt_inc'] . " 00:00:00");
        }
        if(isset($dados['dt_fn'])){
            $queryAbertos->where('created_at', '<=', $dados['dt_fn'] . " 23:59:59");
        }
        if(isset($dados['clinica_id'])){
            $queryAbertos->where('clinica_id', $dados['clinica_id']);
        }
        if(isset($dados['medicamento_id'])){
            $queryAbertos->whereHas('estoque', function($q) use ($dados){
                $q->where('medicamento_id', $dados['medicamento_id']);
            });
        }
        
        $abertos = $queryAbertos->with(['estoque.medicamento', 'clinica', 'user'])->get();

        $movimentacoes = array();
        
        foreach($fechados as $item){
            $movimentacoes[] = [
                'data' => $item->created_at,
                'clinica' => $item->clinica->nome ?? 'N/A',
                'medicamento' => $item->medicamento->nome ?? 'N/A',
                'lote' => $item->lote,
                'quantidade' => $item->quantidade,
                'tipo' => 'Fechado',
                'motivo' => $item->baixa->motivo ?? 'N/A',
                'usuario' => $item->baixa->user->nome ?? 'N/A'
            ];
        }

        foreach($abertos as $item){
            $movimentacoes[] = [
                'data' => $item->created_at,
                'clinica' => $item->clinica->nome ?? 'N/A',
                'medicamento' => $item->estoque->medicamento->nome ?? 'N/A',
                'lote' => $item->estoque->lote ?? 'N/A',
                'quantidade' => $item->quantidade,
                'tipo' => 'Aberto',
                'motivo' => $item->motivo,
                'usuario' => $item->user->nome ?? 'N/A'
            ];
        }

        usort($movimentacoes, function($a, $b) {
            return $b['data'] <=> $a['data'];
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $array_dados = [
            'Data',
            'Clínica',
            'Medicamento',
            'Lote',
            'Quantidade',
            'Tipo',
            'Motivo',
            'Usuário'
        ];

        $linhaAtual = 1;
        $sheet->fromArray($array_dados, null, 'A' . $linhaAtual);

        foreach($movimentacoes as $linha){
            $linhaAtual++;
            $array = [
                $linha['data']->format('d/m/Y H:i'),
                $linha['clinica'],
                $linha['medicamento'],
                $linha['lote'],
                number_format($linha['quantidade'], 2, ',', '.'),
                $linha['tipo'],
                $linha['motivo'],
                $linha['usuario']
            ];
            $sheet->fromArray($array, null, 'A' . $linhaAtual);
        }

        $writer = new Xlsx($spreadsheet);
        $arquivo = "Exportar Relatorio - Baixas - ".date('d.m.Y - H:i').'.xlsx';
        $arquivo = str_replace(":",'h',$arquivo);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$arquivo}\"");
        $writer->save("php://output");
        exit();
    }

    public function recepcao_gerar(Request $request){
        $dados = $request->except('_token');
        
        $query = Procedimento::query();
        
        if($request->dt_inc){
            $query->where('data_cad', '>=', $request->dt_inc);
        }
        if($request->dt_fn){
            $query->where('data_cad', '<=', $request->dt_fn);
        }
        if($request->clinica_id){
            $query->where('clinica_id', $request->clinica_id);
        }
        if($request->user_id_cadastro){
            $query->where('user_id_cadastro', $request->user_id_cadastro);
        }

        // Apenas procedimentos que tenham tanto o início quanto o fim registrados
        $query->whereNotNull('inicio_cadastro')->whereNotNull('finalizacao_cadastro');

        $procedimentos = $query->with(['paciente', 'cadastrante', 'clinica'])
                              ->orderBy('inicio_cadastro', 'desc')
                              ->get();

        return view('adm/relatorios/recepcao_gerar', compact('procedimentos', 'dados'));
    }

    public function caixa(){
        $usuarios = User::where('tipo','Secretária')->where('st_usuario', 'Ativo')->orderBy('nome')->get();
        $clinicas = Clinica::all()->sortBy('nome');
        return view('adm/relatorios/caixa', compact('usuarios','clinicas'));
    }

    public function caixa_gerar(Request $request){
        $dados = $request->except('_token');
        
        $query = \App\Models\FinanceiroFormasPagamento::query();
        
        if($request->dt_inc){
            $query->where('created_at', '>=', $request->dt_inc . " 00:00:00");
        }
        if($request->dt_fn){
            $query->where('created_at', '<=', $request->dt_fn . " 23:59:59");
        }
        if($request->user_id){
            $query->where('user_id_cadastro', $request->user_id);
        }
        if($request->clinica_id){
            $query->whereHas('financeiro', function($q) use ($request){
                $q->where('clinica_id', $request->clinica_id);
            });
        }

        $pagamentos = $query->with(['financeiro.paciente', 'cadastrante'])->orderBy('created_at', 'desc')->get();
        $user_filtro = $request->user_id ? User::find($request->user_id) : null;

        return view('adm/relatorios/caixa_gerar', compact('pagamentos', 'dados', 'user_filtro'));
    }

    public function caixa_diario_sistema(){
        $user = auth()->user();
        if(!$user){
            $user = session()->get('user');
        }

        $pagamentos = \App\Models\FinanceiroFormasPagamento::where('user_id_cadastro', $user->id)
        ->where('created_at', '>=', date('Y-m-d').' 00:00:00')
        ->where('created_at', '<=', date('Y-m-d').' 23:59:59')
        ->with('financeiro.paciente')
        ->get();

        return view('sistema/relatorios/caixa_diario', compact('pagamentos','user'));
    }
    public function estoque(){
        $clinicas = Clinica::all()->sortBy('nome');
        $medicamentos = Medicamento::all()->sortBy('nome');
        return view('adm/relatorios/estoque', compact('clinicas','medicamentos'));
    }

    public function estoque_gerar(Request $request){
        $dados = $request->except('_token');
        
        $query = \App\Models\Estoque::query();
        
        if($request->clinica_id){
            $query->where('clinica_id', $request->clinica_id);
        }
        if($request->medicamento_id){
            $query->where('medicamento_id', $request->medicamento_id);
        }

        $estoques = $query->with(['medicamento', 'clinica'])->get();

        $agrupados = [];

        foreach($estoques as $estoque){
            $chave = $estoque->clinica_id . '_' . $estoque->medicamento_id . '_' . $estoque->lote . '_' . $estoque->codigo_barras;
            
            if(!isset($agrupados[$chave])){
                $agrupados[$chave] = [
                    'clinica' => $estoque->clinica->nome ?? 'N/A',
                    'medicamento' => $estoque->medicamento->nome ?? 'N/A',
                    'lote' => $estoque->lote,
                    'codigo_barras' => $estoque->codigo_barras,
                    'dt_vencimento' => $estoque->dt_vencimento,
                    'saldo' => 0
                ];
            }
            
            if($estoque->tipo == 'Entrada'){
                $agrupados[$chave]['saldo'] += $estoque->quantidade;
            } else {
                $agrupados[$chave]['saldo'] -= $estoque->quantidade;
            }
        }

        // Filtra apenas saldos > 0
        $resultados = array_filter($agrupados, function($item) {
            return $item['saldo'] > 0;
        });

        // Ordena por medicamento
        usort($resultados, function($a, $b) {
            return strcmp($a['medicamento'], $b['medicamento']);
        });

        return view('adm/relatorios/estoque_gerar', compact('resultados', 'dados'));
    }

    public function exportar_estoque(Request $request){
        $dados = json_decode($request->dados, true);
        
        $query = \App\Models\Estoque::query();
        if(isset($dados['clinica_id']) && $dados['clinica_id']){
            $query->where('clinica_id', $dados['clinica_id']);
        }
        if(isset($dados['medicamento_id']) && $dados['medicamento_id']){
            $query->where('medicamento_id', $dados['medicamento_id']);
        }

        $estoques = $query->with(['medicamento', 'clinica'])->get();

        $agrupados = [];
        foreach($estoques as $estoque){
            $chave = $estoque->clinica_id . '_' . $estoque->medicamento_id . '_' . $estoque->lote . '_' . $estoque->codigo_barras;
            if(!isset($agrupados[$chave])){
                $agrupados[$chave] = [
                    'clinica' => $estoque->clinica->nome ?? 'N/A',
                    'medicamento' => $estoque->medicamento->nome ?? 'N/A',
                    'lote' => $estoque->lote,
                    'codigo_barras' => $estoque->codigo_barras,
                    'dt_vencimento' => $estoque->dt_vencimento,
                    'saldo' => 0
                ];
            }
            if($estoque->tipo == 'Entrada'){
                $agrupados[$chave]['saldo'] += $estoque->quantidade;
            } else {
                $agrupados[$chave]['saldo'] -= $estoque->quantidade;
            }
        }

        $resultados = array_filter($agrupados, function($item) {
            return $item['saldo'] > 0;
        });

        usort($resultados, function($a, $b) {
            return strcmp($a['medicamento'], $b['medicamento']);
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $cabecalho = [
            'Clínica', 'Medicamento', 'Código de Barras', 'Lote', 'Vencimento', 'Quantidade em Estoque'
        ];
        $sheet->fromArray($cabecalho, null, 'A1');

        $linhaTotal = 2;
        foreach($resultados as $linha){
            $array_excel = [
                $linha['clinica'],
                $linha['medicamento'],
                $linha['codigo_barras'],
                $linha['lote'],
                dataDbForm($linha['dt_vencimento']),
                $linha['saldo']
            ];
            $sheet->fromArray($array_excel, null, 'A' . $linhaTotal);
            $linhaTotal++;
        }

        $arq = "Estoque_".date('YmdHis');
        $path = public_path('rel_estoque/'.$arq.'.xlsx');
        if(!is_dir(public_path('rel_estoque'))){
            mkdir(public_path('rel_estoque'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }

    public function financeiro_simplificado(){
        $clinicas = Clinica::all()->sortBy('nome');
        return view('adm/relatorios/financeiro_simplificado', compact('clinicas'));
    }

    public function financeiro_simplificado_gerar(Request $request){
        $dados = $request->except('_token');
        $financeiros = Financeiro::get_pagamentos_relatorio($dados);

        $dt_inc = false;
        $dt_fn = false;

        if($request->dt_inc){
            $dt_inc = $request->dt_inc;
            $dt_inc_stamp = strtotime($dt_inc." 00:00:00");
        }

        if($request->dt_fn){
            $dt_fn = $request->dt_fn;
            $dt_fn_stamp = strtotime($dt_fn." 23:59:59");
        }

        $array_financeiro = array();

        foreach($financeiros as $financeiro){
            foreach($financeiro->formas as $forma){
                $dt_forma_stamp = strtotime($forma->created_at);
                if( (!$dt_inc || $dt_forma_stamp >= $dt_inc_stamp ) && (!$dt_fn || $dt_forma_stamp <= $dt_fn_stamp) ){
                    $procedimento = $financeiro->procedimentos()->first();
                    $rateio_pagamento = $forma->get_rateio_financeiro();
                    $var = explode(" ",$forma->created_at);
                    $data = $var[0];
                    $contador = $procedimento ? Procedimento::where('codigo', $procedimento->codigo)->count() : '0';
                    $codigo = $procedimento ? $procedimento->codigo : '';

                    $vl_consulta = floatval($rateio_pagamento['vl_consulta'] ?? 0);
                    $vl_procedimento_detalhe = floatval($rateio_pagamento['vl_procedimento'] ?? 0);
                    
                    if (isset($rateio_pagamento['detalhes_procedimentos']) && count($rateio_pagamento['detalhes_procedimentos']) > 0) {
                        $vl_procedimento_detalhe = 0;
                        foreach($rateio_pagamento['detalhes_procedimentos'] as $dp){
                            $vl_procedimento_detalhe += floatval($dp['valor'] ?? 0);
                        }
                    }

                    $vl_procedimentos_total = $vl_consulta + $vl_procedimento_detalhe;
                    $vl_aplicacoes_total = floatval($rateio_pagamento['vl_aplicacao'] ?? 0);

                    if (($vl_procedimentos_total + $vl_aplicacoes_total) > 0) {
                        $linha_dados = [
                            'financeiro_id' => $forma->financeiro_id,
                            'pagamento_id' => $forma->id,
                            'ordem' => strtotime($data),
                            'data' => dataDbForm($data),
                            'paciente' => $financeiro->paciente->nm_paciente,
                            'paciente_id' => $financeiro->paciente->id,
                            'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                            'cpf' => $financeiro->paciente->cpf,
                            'codigo' => $codigo,
                            'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                            'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                            'vl_procedimentos' => 'R$ '.valorDbForm($vl_procedimentos_total),
                            'vl_aplicacoes' => 'R$ '.valorDbForm($vl_aplicacoes_total),
                            'tipo_atendimento' => $procedimento ? $procedimento->tipo_atendimento : '',
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'id_pagamento' => $forma->id_pagamento,
                            'parcelas' => $forma->parcelas,
                            'obs' => $financeiro->obs_pagamento,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                        ];
                        $array_financeiro[] = $linha_dados;
                    }
                }
            }
        }

        usort($array_financeiro, function($a, $b) {
            return $a['ordem'] <=> $b['ordem'];
        });

        return view('adm/relatorios/financeiro_simplificado_gerar', compact('array_financeiro', 'dados'));
    }

    public function exportar_financeiro_simplificado(Request $request){
        $dados_req = json_decode($request->dados, true);
        $financeiros = Financeiro::get_pagamentos_relatorio($dados_req);

        $dt_inc = false;
        $dt_fn = false;

        if(isset($dados_req['dt_inc']) && $dados_req['dt_inc']){
            $dt_inc = $dados_req['dt_inc'];
            $dt_inc_stamp = strtotime($dt_inc." 00:00:00");
        }

        if(isset($dados_req['dt_fn']) && $dados_req['dt_fn']){
            $dt_fn = $dados_req['dt_fn'];
            $dt_fn_stamp = strtotime($dt_fn." 23:59:59");
        }

        $array_financeiro = array();

        foreach($financeiros as $financeiro){
            foreach($financeiro->formas as $forma){
                $dt_forma_stamp = strtotime($forma->created_at);
                if( (!$dt_inc || $dt_forma_stamp >= $dt_inc_stamp ) && (!$dt_fn || $dt_forma_stamp <= $dt_fn_stamp) ){
                    $procedimento = $financeiro->procedimentos()->first();
                    $rateio_pagamento = $forma->get_rateio_financeiro();
                    $var = explode(" ",$forma->created_at);
                    $data = $var[0];
                    $contador = $procedimento ? Procedimento::where('codigo', $procedimento->codigo)->count() : '0';
                    $codigo = $procedimento ? $procedimento->codigo : '';

                    $vl_consulta = floatval($rateio_pagamento['vl_consulta'] ?? 0);
                    $vl_procedimento_detalhe = floatval($rateio_pagamento['vl_procedimento'] ?? 0);
                    
                    if (isset($rateio_pagamento['detalhes_procedimentos']) && count($rateio_pagamento['detalhes_procedimentos']) > 0) {
                        $vl_procedimento_detalhe = 0;
                        foreach($rateio_pagamento['detalhes_procedimentos'] as $dp){
                            $vl_procedimento_detalhe += floatval($dp['valor'] ?? 0);
                        }
                    }

                    $vl_procedimentos_total = $vl_consulta + $vl_procedimento_detalhe;
                    $vl_aplicacoes_total = floatval($rateio_pagamento['vl_aplicacao'] ?? 0);

                    if (($vl_procedimentos_total + $vl_aplicacoes_total) > 0) {
                        $dados = [
                            'pagamento_id' => $forma->id,
                            'ordem' => strtotime($data),
                            'data' => dataDbForm($data),
                            'paciente' => $financeiro->paciente->nm_paciente,
                            'id_feegow' => $financeiro->paciente->paciente_id_feegow,
                            'cpf' => $financeiro->paciente->cpf,
                            'codigo' => $codigo,
                            'vl_tratamento' => 'R$ '.valorDbForm($financeiro->vl_procedimentos),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'vl_pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                            'vl_procedimentos' => 'R$ '.valorDbForm($vl_procedimentos_total),
                            'vl_aplicacoes' => 'R$ '.valorDbForm($vl_aplicacoes_total),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'id_pagamento' => $forma->id_pagamento,
                            'parcelas' => $forma->parcelas,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                            'obs' => $financeiro->obs_pagamento,
                        ];
                        $array_financeiro[] = $dados;
                    }
                }
            }
        }

        usort($array_financeiro, function($a, $b) {
            return $a['ordem'] <=> $b['ordem'];
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $cabecalho = [
            'ID', 'Data', 'Paciente', 'ID Feegow', 'CPF', 'Codigo', 'Valor Tratamento', 'Desconto Total',
            'Pagamento', 'Procedimentos', 'Aplicações', 'Forma Pagamento', 'ID Pagamento', 'Parcelas',
            'Clinica', 'Médico', 'Nr Procedimentos', 'Obs'
        ];

        $sheet->fromArray($cabecalho, null, 'A1');

        $linhaTotal = 2;
        foreach($array_financeiro as $linha){
            $array_excel = [
                $linha['pagamento_id'],
                $linha['data'],
                $linha['paciente'],
                $linha['id_feegow'],
                $linha['cpf'],
                $linha['codigo'],
                $linha['vl_tratamento'],
                $linha['desconto_total'],
                $linha['vl_pagamento'],
                $linha['vl_procedimentos'],
                $linha['vl_aplicacoes'],
                $linha['forma_pagamento'],
                $linha['id_pagamento'],
                $linha['parcelas'],
                $linha['clinica'],
                $linha['medico'],
                $linha['contador'],
                $linha['obs']
            ];
            $sheet->fromArray($array_excel, null, 'A' . $linhaTotal);
            $linhaTotal++;
        }

        $arq = "Financeiro_Simplificado_".date('YmdHis');
        $path = public_path('rel_financeiro/'.$arq.'.xlsx');
        if(!is_dir(public_path('rel_financeiro'))){
            mkdir(public_path('rel_financeiro'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }
}
