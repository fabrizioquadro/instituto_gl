<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paciente;
use App\Models\Configuracao;
use App\Models\Procedimento;

class PacienteSistemaController extends Controller
{
    public function index(){
        return view('sistema/pacientes/index');
    }

    public function index_pesq(Request $request){
        $requestData = $_REQUEST;

        $totalData = Paciente::count();
        $totalFiltered = $totalData;

        $query = Paciente::query();

        if (!empty($requestData['search']['value'])) {
            $searchValue = $requestData['search']['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('nm_paciente', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('paciente_id_feegow', 'LIKE', '%' . $searchValue . '%')
                  ->orWhere('cpf', 'LIKE', '%' . $searchValue . '%');
            });
            $totalFiltered = $query->count();
        }

        $columns = [
            0 => 'nm_paciente',
            1 => 'paciente_id_feegow'
        ];

        if (isset($requestData['order'])) {
            $columnIndex = $requestData['order'][0]['column'];
            $columnName = $columns[$columnIndex] ?? 'nm_paciente';
            $columnSortOrder = $requestData['order'][0]['dir'];
            $query->orderBy($columnName, $columnSortOrder);
        } else {
            $query->orderBy('nm_paciente', 'asc');
        }

        $pacientes = $query->offset($requestData['start'] ?? 0)
                           ->limit($requestData['length'] ?? 10)
                           ->get();

        $dados = [];
        foreach ($pacientes as $paciente) {
            $dado = [];
            $dado[] = $paciente->nm_paciente;
            $dado[] = $paciente->paciente_id_feegow;
            $acoes = '<a href="' . route('sistema.pacientes.procedimentos', $paciente->id) . '" class="btn btn-sm btn-primary">Procedimentos</a>';
            $acoes .= '<button type="button" onclick="abrir_obs(' . $paciente->id . ')" class="btn btn-sm btn-info ms-2"><span class="tf-icons mdi mdi-comment-text-outline me-1"></span> Obs</button>';
            $dado[] = $acoes;
            $dados[] = $dado;
        }

        $json_data = [
            "draw" => intval($requestData['draw'] ?? 1),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $dados
        ];

        return response()->json($json_data);
    }

    public function atualizar_integracao(){
        set_time_limit(0);
        // Flag para controlar a integração com o Kamino (desabilitado para evitar timeout)
        // Mude para true quando quiser reativar a integração com o Kamino
        $integrar_kamino = false;
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

            // ===== INTEGRAÇÃO COM KAMINO (DESABILITADA) =====
            if ($integrar_kamino) {
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
                        try {
                            $retorno = json_decode($api_kamino->import_cliente(json_encode($dados)));
                            if(isset($retorno) && $retorno->Sucesso == true){
                                $paciente->integrado_kamino = 'Sim';
                                $paciente->save();
                            }
                        } catch (\Exception $e) {
                            $erro_kamino = true;
                        }
                    }
                }
            }
            // ===== FIM INTEGRAÇÃO KAMINO =====

            $configuracao->ultima_atualizacao_pacientes = date('Y-m-d');
            $configuracao->save();
            
            if (isset($erro_kamino) && $erro_kamino) {
                return redirect()->route('sistema.pacientes')->with('mensagem', 'Pacientes atualizados (Feegow) com sucesso. Porém, a integração com o Kamino falhou (chave de acesso inválida/expirada).');
            }
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

    public function salvar_obs_ajax(Request $request){
        try {
            $paciente = Paciente::where('id', $request->paciente_id)->first();
            if($paciente){
                $paciente->obs = $request->obs;
                $paciente->save();
                return response()->json([
                    'success' => true,
                    'message' => 'Observações atualizadas com sucesso!'
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
