<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Procedimento;
use App\Models\Aplicacao;
use App\Models\AplicacaoLote;
use App\Models\Financeiro;
use App\Models\FinanceiroProcedimento;
use App\Models\FinanceiroFormasPagamento;

class MigracaoController extends Controller
{
    public function index(){
        die('acabou no inicio');
        $db = mysqli_connect("localhost", "u528878205_migracao2", "P&dr0Quadr0", "u528878205_migracao2");
        if (!$db){
            echo "Error: Unable to connect to MySQL." . PHP_EOL;
            echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
            echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
            exit;
        }//if (!$link)
        /*
        $sql = "SELECT DISTINCT codigo FROM procedimentos WHERE
        clinica_id='6' AND
        paciente_id<>'5821' AND
        paciente_id<>'6052' AND
        paciente_id<>'13250' AND
        paciente_id<>'13253' AND
        paciente_id<>'13272' AND
        paciente_id<>'13384' AND
        paciente_id<>'17359' AND
        paciente_id<>'17626' AND
        paciente_id<>'17627' AND
        paciente_id<>'17648' AND
        paciente_id<>'18048' AND
        paciente_id<>'7574'";
        $res = mysqli_query($db, $sql);
        */

        //vamos fazer o caso da Larissa Abolafio que faltou
        $sql = "SELECT DISTINCT codigo FROM procedimentos WHERE
        id='833'";
        $res = mysqli_query($db, $sql);

        foreach($res as $linha){
            $sql = "SELECT * FROM procedimentos WHERE codigo='$linha[codigo]'";
            $procs = mysqli_query($db, $sql);

            foreach($procs as $proc){
                $dados = [
                    'codigo' => $proc['codigo'],
                    'nr_procedimento' => $proc['nr_procedimento'],
                    'clinica_id' => '6',
                    'clinica_id_aplicacao' => $proc['clinica_id_aplicacao'],
                    'paciente_id' => $proc['paciente_id'],
                    'user_id_aplicacao' => $proc['user_id_aplicacao'],
                    'data_cad' => $proc['data_cad'],
                    'data_aplicacao' => $proc['data_aplicacao'],
                    'data_pagamento' => $proc['data_pagamento'],
                    'valor' => $proc['valor'],
                    'st_pagamento' => $proc['st_pagamento'],
                    'situacao' => $proc['situacao'],
                    'medico' => $proc['medico'],
                    'obs' => $proc['obs'],
                    'tipo_pagamento' => $proc['tipo_pagamento'],
                    'forma_pagamento' => $proc['forma_pagamento'],
                    'parcelas' => $proc['parcelas'],
                    'vl_pago' => $proc['vl_pago'],
                    'obs_pagamento' => $proc['obs_pagamento'],
                    'st_biopedancia' => $proc['st_biopedancia'],
                    'obs_biopedancia' => $proc['obs_biopedancia'],
                    'st_coleta' => $proc['st_coleta'],
                    'tp_coleta' => $proc['tp_coleta'],
                    'obs_coleta' => $proc['obs_coleta'],
                    'semana_sem_aplicacao' => $proc['semana_sem_aplicacao'],
                    'autorizador_sem_pagamento' => $proc['autorizador_sem_pagamento'],
                    'consulta_tratamento_agendada' => $proc['consulta_tratamento_agendada'],
                    'created_at' => $proc['created_at'],
                    'updated_at' => $proc['updated_at'],
                ];

                $procedimento = Procedimento::create($dados);

                $sql = "SELECT * FROM aplicacaos WHERE procedimento_id='$proc[id]'";
                $aplics = mysqli_query($db, $sql);

                foreach($aplics as $aplic){
                    $dados = [
                        'procedimento_id' => $procedimento->id,
                        'medicamento_id' => $aplic['medicamento_id'],
                        'user_id_aplicacao' => $aplic['user_id_aplicacao'],
                        'quantidade' => $aplic['quantidade'],
                        'valor' => $aplic['valor'],
                        'total' => $aplic['total'],
                        'situacao' => $aplic['situacao'],
                        'obs' => $aplic['obs'],
                        'created_at' => $aplic['created_at'],
                        'updated_at' => $aplic['updated_at'],
                    ];

                    $aplicacao = Aplicacao::create($dados);

                    $sql = "SELECT * FROM aplicacao_lotes WHERE aplicacao_id='$aplic[id]'";
                    $lotes = mysqli_query($db, $sql);
                    foreach($lotes as $lote){
                        $dados = [
                            'aplicacao_id' => $aplicacao->id,
                            'quantidade' => $lote['quantidade'],
                            'lote' => $lote['lote'],
                            'codigo_barras' => $lote['codigo_barras'],
                        ];

                        AplicacaoLote::create($dados);
                    }
                }
            }
        }
    }

    public function financeiro(){
        //die('acabou');
        $db = mysqli_connect("localhost", "u528878205_migracao2", "P&dr0Quadr0", "u528878205_migracao2");
        if (!$db){
            echo "Error: Unable to connect to MySQL." . PHP_EOL;
            echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
            echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
            exit;
        }//if (!$link)
        /*
        $sql = "SELECT DISTINCT codigo FROM procedimentos WHERE
        clinica_id='6' AND
        paciente_id<>'5821' AND
        paciente_id<>'6052' AND
        paciente_id<>'13250' AND
        paciente_id<>'13253' AND
        paciente_id<>'13272' AND
        paciente_id<>'13384' AND
        paciente_id<>'17359' AND
        paciente_id<>'17626' AND
        paciente_id<>'17627' AND
        paciente_id<>'17648' AND
        paciente_id<>'18048' AND
        paciente_id<>'7574'";
        */

        //vamos fazer o caso da Larissa Abolafio que faltou
        $sql = "SELECT DISTINCT codigo FROM procedimentos WHERE
        id='833'";
        $codigos = mysqli_query($db, $sql);

        foreach($codigos as $codigo){
            $financeiro = false;

            //vamos buscar o financeiro do procedimento
            $sql = "SELECT * FROM procedimentos WHERE
            codigo='$codigo[codigo]' AND situacao<>'Semana Sem Aplicação'";
            $res = mysqli_query($db, $sql);
            $procedimento = mysqli_fetch_assoc($res);

            $sql = "SELECT * FROM financeiro_procedimentos WHERE procedimento_id='$procedimento[id]'";
            $res = mysqli_query($db, $sql);
            if(mysqli_num_rows($res) > 0){
                $linha = mysqli_fetch_assoc($res);
                $sql = "SELECT * FROM financeiros WHERE id='$linha[financeiro_id]'";
                $res = mysqli_query($db, $sql);
                $financeiro = mysqli_fetch_assoc($res);
            }

            $controle_formas = 0;
            if($financeiro){
                $sql = "SELECT * FROM financeiro_formas_pagamentos WHERE financeiro_id='$financeiro[id]'";
                $formas = mysqli_query($db, $sql);
                $controle_formas = mysqli_num_rows($formas);
            }

            //vamos descobrir se já há um financeiro criado no novo
            $n_procedimentos = Procedimento::where('codigo', $codigo['codigo'])->get();
            $n_financeiro_id = false;
            foreach($n_procedimentos as $n_procedimento){
                if(!$n_financeiro_id){
                    $var = FinanceiroProcedimento::where('procedimento_id', $n_procedimento->id)->first();
                    if($var){
                        $n_financeiro_id = $var->financeiro_id;
                    }
                }
            }

            if($n_financeiro_id){
                $n_financeiro = Financeiro::where('id', $n_financeiro_id)->first();
            }
            else{
                $dados = [
                    'clinica_id' => $procedimento['clinica_id'],
                    'paciente_id' => $procedimento['paciente_id'],
                    'medico' => $procedimento['medico'],
                    'vl_consulta' => '0.00',
                    'vl_procedimentos' => '0.00',
                    'vl_desconto' => '0.00',
                    'vl_pagamento' => '0.00',
                    'forma_pagamento' => 'teste',
                    'tipo_pagamento' => 'migração',
                    'parcelas' => '0',
                    'obs_pagamento' => 'Financeiro elaborado na migração',
                ];

                $n_financeiro = Financeiro::create($dados);
            }

            //vamos adicionar todos os n_procedimentos no novo financeiro
            $n_procedimentos = Procedimento::where('codigo', $codigo['codigo'])->get();
            $n_vl_procedimentos = 0;
            foreach($n_procedimentos as $n_procedimento){
                $n_vl_procedimentos += $n_procedimento->valor;

                FinanceiroProcedimento::where('procedimento_id', $n_procedimento->id)->delete();
                $dados = [
                    'financeiro_id' => $n_financeiro->id,
                    'procedimento_id' => $n_procedimento->id,
                ];

                FinanceiroProcedimento::create($dados);

            }

            $n_financeiro->vl_procedimentos = $n_vl_procedimentos;
            $n_financeiro->save();

            if($financeiro && $controle_formas > 0){
                foreach($formas as $forma){
                    $dados = [
                        'financeiro_id' => $n_financeiro->id,
                        'forma_pagamento' => $forma['forma_pagamento'],
                        'parcelas' => $forma['parcelas'],
                        'vl_pagamento' => $forma['vl_pagamento'],
                    ];
                    FinanceiroFormasPagamento::create($dados);
                }
            }
            else{
                //se entrar aqui é que não existia financeiro ou não tinha formas de pagamento

                //vamos buscar todos os procedimentos desse grupo do DB antigo e os que tiverem pagamento lançar no db
                $sql = "SELECT * FROM procedimentos WHERE codigo='$codigo[codigo]' AND valor>'0'";
                $procedimentos = mysqli_query($db, $sql);

                foreach($procedimentos as $procedimento){
                    if($procedimento['st_pagamento'] == 'Sim'){
                        $forma_pagamento = $procedimento['forma_pagamento'] ? $procedimento['forma_pagamento'] : 'Pagamento';
                        $parcelas = $procedimento['parcelas'] ? $procedimento['parcelas'] : '1';
                        $dados = [
                            'financeiro_id' => $n_financeiro->id,
                            'forma_pagamento' => $forma_pagamento,
                            'parcelas' => $parcelas,
                            'vl_pagamento' => $procedimento['valor'],
                        ];

                        FinanceiroFormasPagamento::create($dados);
                    }
                }
            }

            //vamos chamar a funcao que atualiza a situação de pagamentos
            FinanceiroSistemaController::atualiza_financeiro_procedimento($codigo['codigo']);
        }
    }
}
