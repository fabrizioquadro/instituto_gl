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
        $enfermeiras = User::where('tipo','Enfermagem')->orderBy('nome')->get();
        $clinicas = Clinica::all()->sortBy('nome');
        return view('adm/relatorios/enfermagem', compact('clinicas','enfermeiras'));
    }

    public function transferencias(){
        return view('adm/relatorios/transferencias');
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
                        $dados = [
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
                            'desconto' => valorDbForm(0.00),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'parcelas' => $forma->parcelas,
                            'obs' => $financeiro->obs_pagamento,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
                        ];
                        $array_financeiro[] = $dados;
                    }

                    if($rateio_pagamento['vl_aplicacao'] > 0){
                        $dados = [
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
                            'desconto' => valorDbForm($financeiro->vl_desconto),
                            'desconto_total' => 'R$ '.valorDbForm($financeiro->vl_desconto),
                            'forma_pagamento' => $forma->forma_pagamento,
                            'parcelas' => $forma->parcelas,
                            'obs' => $financeiro->obs_pagamento,
                            'clinica' => $financeiro->clinica->nome,
                            'medico' => $financeiro->medico,
                            'contador' => $contador,
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

        return view('adm/relatorios/financeiro_gerar', compact('array_financeiro'));
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
        $medicamento_id = $dados['medicamento_id'];
        $situacao = $dados['situacao'];

        return view('adm/relatorios/vendas_gerar', compact('procedimentos','medicamento_id','situacao'));
    }

    public function enfermagem_gerar(Request $request){
        $dados = $request->except('_token');
        $procedimentos = Procedimento::gerar_relatorio_enfermagem($dados);

        return view('adm/relatorios/enfermagem_gerar', compact('procedimentos','dados'));
    }

    public function transferencias_gerar(Request $request){
        $dados = $request->except('_token');
        $transferencias = Transferencia::gerar_relatorio_transferencias($dados);

        return view('adm/relatorios/transferencias_gerar', compact('transferencias'));
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
            'Finalização',
            'Paciente',
            'Enfermeira',
            'Medicamento',
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
                    $linhaAtual++;

                    $var = explode(' ', $aplicacao->updated_at);
                    $data = dataDbForm($var[0]);
                    $hora = $var[1];

                    $array_dados = [
                        $chegada,
                        $atendimento,
                        $finalizacao,
                        $procedimento->paciente->nm_paciente,
                        $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '',
                        $aplicacao->medicamento->nome,
                        'R$ '.valorDbForm($aplicacao->total),
                        $aplicacao->lotes(),
                        $aplicacao->codigos(),
                        $aplicacao->vencimentos(),
                        $aplicacao->obs,
                        $procedimento->codigo.'/'.$procedimento->nr_procedimento,
                        $procedimento->st_pagamento,
                        $procedimento->flag_coordenacao == 1 ? 'Sim' : 'Não',
                        $procedimento->flag_qualidade == 1 ? 'Sim' : 'Não'
                    ];
                    $sheet->fromArray($array_dados, null, 'A' . $linhaAtual);
                }
            }
        }

        // Caminho onde o arquivo será salvo
        $arq = "Enfermagem_".date('YmdHis');
        $path = public_path('rel_enfermagem/'.$arq.'.xlsx');

        // Salva o arquivo
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend(false);
    }

}
