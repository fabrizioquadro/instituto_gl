<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paciente;
use App\Models\Configuracao;
use App\Models\Procedimento;

class PacienteSistemaController extends Controller
{
    public function index(){
        $pacientes = Paciente::all();
        return view('sistema/pacientes/index', compact('pacientes'));
    }

    public function atualizar_integracao(){
        try {
            $configuracao = Configuracao::where('id', '1')->first();
            $api = api();
            $pacientes = $api->get_pacientes($configuracao->ultima_atualizacao_pacientes);

            $api_kamino = new ApiKaminoController();

            $i = 0;
            foreach($pacientes as $linha){
                $retorno = $api->get_nome_paciente($linha['paciente_id']);
                if($retorno['success'] == true){

                    $dados_paciente = $retorno['content'];

                    if((isset($dados_paciente['nome']) || isset($dados_paciente['nome_social'])) && isset($dados_paciente['documentos']['cpf'])){
                        $nome = isset($dados_paciente['nome']) ? $dados_paciente['nome'] : $dados_paciente['nome_social'];
                        $dt_nascimento = null;
                        if($dados_paciente['nascimento']){
                            $var = explode('-', $dados_paciente['nascimento']);
                            $dt_nascimento = $var[2].'-'.$var[1].'-'.$var[0];
                        }

                        $telefones = null;
                        if($dados_paciente['telefones']){
                            $telefones = $dados_paciente['telefones'][0]." ".$dados_paciente['telefones'][1]." ".$dados_paciente['celulares'][0]." ".$dados_paciente['celulares'][1];
                        }

                        $email = null;
                        if($dados_paciente['email']){
                            $email = $dados_paciente['email'][0]." ".$dados_paciente['email'][1];
                        }

                        $dados_import = [
                            'nm_paciente' => $nome,
                            'dt_nascimento' => $dt_nascimento,
                            'cpf' => $dados_paciente['documentos']['cpf'],
                            'paciente_id_feegow' => $dados_paciente['id'],
                            'endereco' => $dados_paciente['endereco'],
                            'numero' => $dados_paciente['numero'],
                            'complemento' => $dados_paciente['complemento'],
                            'bairro' => $dados_paciente['bairro'],
                            'cidade' => $dados_paciente['cidade'],
                            'estado' => $dados_paciente['estado'],
                            'cep' => $dados_paciente['cep'],
                            'telefone' => $telefones,
                            'email' => $email,
                        ];

                        //vamos verificar se ja existe esse paciente da feegow no nosso sistema
                        if($paciente = Paciente::where('paciente_id_feegow', $dados_import['paciente_id_feegow'])->first()){
                            $paciente->update($dados_import);
                        }
                        else{
                            $dados_import['integrado_kamino'] = 'Não';
                            $paciente = Paciente::create($dados_import);                            
                        }
                    }
                }
            }

            //vamos buscar todos os pacientes que não foram integrado ao kamino
            $pacientes = Paciente::where('integrado_kamino', 'Não')->get();
            foreach($pacientes as $paciente){
                if($paciente->cpf != ""){

                    $dados = [
                        'Nome' => $paciente->nm_paciente,
                        'CPFCNPJ' => $paciente->cpf,
                        'Cliente' => true,
                        'Logradouro' => $paciente->endereco,
                        'Nro' => $paciente->numero,
                        'Complemento' => $paciente->complemento,
                        'Bairro' => $paciente->bairro,
                        'CEP' => $paciente->cep,
                        'Cidade' => $paciente->cidade,
                        'UF' => $paciente->estado,
                        'EmailPrincipal' => $paciente->email,
                        'TelefonePrincipal' => $paciente->telefone,
                    ];

                    $api_kamino = new ApiKaminoController();
                    $retorno = json_decode($api_kamino->import_cliente(json_encode($dados)));
                    if(isset($retorno) && $retorno->Sucesso == true){
                        $paciente->integrado_kamino = 'Sim';
                        $paciente->save();
                        //dd($paciente);
                    }
                }
            }

            $configuracao->ultima_atualizacao_pacientes = date('Y-m-d');
            $configuracao->save();
            return redirect()->route('sistema.pacientes')->with('mensagem', 'Integração Atualizada');
        } catch (\Exception $e) {
            return redirect()->route('sistema.pacientes')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function listar_pacientes_ajax(){
        if(isset($_GET['q'])){
            $procurar = $_GET['q'];
            $pacientes = Paciente::where('nm_paciente','LIKE','%'.$procurar.'%')->get();
            $array_retorno = array();
            foreach($pacientes as $paciente){
                $array = [
                    'id' => $paciente->id,
                    'text' => $paciente->nm_paciente." - ".dataDbForm($paciente->dt_nascimento),
                ];
                $array_retorno[] = $array;
            }

            echo json_encode($array_retorno);

        }
    }

    public function get_paciente_ajax(){
        if(isset($_GET['paciente_id'])){
            $paciente = Paciente::where('id', $_GET['paciente_id'])->first();
            return json_encode($paciente);
        }
    }

    public function procedimentos($id){
        $paciente = Paciente::where('id', $id)->first();
        $procedimentos = Procedimento::where('paciente_id', $paciente->id)->orderBy('data_cad')->get();
        return view('sistema/pacientes/procedimentos', compact('paciente','procedimentos'));
    }
}
