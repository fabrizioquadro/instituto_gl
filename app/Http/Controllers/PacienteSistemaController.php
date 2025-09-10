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

            foreach($pacientes as $paciente){
                if($paciente['paciente_nome']){
                    if(!Paciente::where('paciente_id_feegow',$paciente['paciente_id'])->first()){
                        $dados = [
                            'nm_paciente' => $paciente['paciente_nome'],
                            'paciente_id_feegow' => $paciente['paciente_id'],
                        ];
                        Paciente::create($dados);
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

    public function procedimentos($id){
        $paciente = Paciente::where('id', $id)->first();
        $procedimentos = Procedimento::where('paciente_id', $paciente->id)->orderBy('data_cad')->get();
        return view('sistema/pacientes/procedimentos', compact('paciente','procedimentos'));
    }
}
