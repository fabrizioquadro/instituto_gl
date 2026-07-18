<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aplicacao;
use App\Models\Procedimento;
use App\Models\ProcedimentoAnexo;

class ApiFlegowController extends Controller
{
    protected $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmZWVnb3ciLCJhdWQiOiJwdWJsaWNhcGkiLCJpYXQiOjE3NTE4OTczODYsImxpY2Vuc2VJRCI6MjMyMjR9.ZC8gSWEiCJsLa7AoFUOT074zaRNECddfXJNT_zi8RvI";

    public function get_unidades(){
        $apiUrl = "https://api.feegow.com/v1/api/company/list-unity";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
        }
        curl_close($ch);

        //vamos montar o array de retorno com as unidades
        $array_retorno = array();

        //vamos buscar as matriz
        foreach($retorno['content']['matriz'] as $unidade){
            $array = array();
            $array['unidade_id'] = $unidade['unidade_id'];
            $array['nome'] = $unidade['nome_fantasia'];
            $array['cnpj'] = $unidade['cnpj'];
            $array_retorno[] = $array;
        }

        //vamos buscar as unidades
        foreach($retorno['content']['unidades'] as $unidade){
            $array = array();
            $array['unidade_id'] = $unidade['unidade_id'];
            $array['nome'] = $unidade['nome_fantasia'];
            $array['cnpj'] = $unidade['cnpj'];
            $array_retorno[] = $array;
        }

        return $array_retorno;
    }

    public function get_pacientes($ultima_atualizacao = null){
        $parametros = [
            'limit' => '5000',
        ];
        $array_retorno = array();
        while(strtotime(date('Y-m-d')) >= strtotime($ultima_atualizacao)){
            $parametros['alterado_em'] = $ultima_atualizacao;

            $apiUrl = "https://api.feegow.com/v1/api/patient/list?".http_build_query($parametros);
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Define explicitamente o método GET (opcional)
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "x-access-token: $this->token",
                "Content-Type: application/json"
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Erro na requisição: ' . curl_error($ch);
            } else {
                $retorno = json_decode($response, true);
            }
            curl_close($ch);

            foreach($retorno['content'] as $paciente){
                $array = array();
                $array['paciente_id'] = $paciente['patient_id'];
                $array['paciente_nome'] = $paciente['nome'];
                $array['dt_nascimento'] = $paciente['nascimento'];
                $array_retorno[] = $array;
            }

            $ultima_atualizacao = date('Y-m-d', strtotime("+1 days",strtotime($ultima_atualizacao)));
        }

        return $array_retorno;
    }

    public function get_pacientes_limit($limit, $offset){
        $parametros = [
            'limit' => $limit,
            'offset' => $offset,
        ];
        $array_retorno = array();

        $apiUrl = "https://api.feegow.com/v1/api/patient/list?".http_build_query($parametros);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
        }
        curl_close($ch);

        foreach($retorno['content'] as $paciente){
            $array = array();
            $array['paciente_id'] = $paciente['patient_id'];
            $array['paciente_nome'] = $paciente['nome'];
            $array['nascimento'] = $paciente['nascimento'];
            $array_retorno[] = $array;
        }

        return $array_retorno;
    }

    public function get_medicos(){

        $apiUrl = "https://api.feegow.com/v1/api/professional/list";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
        }
        curl_close($ch);

        //vamos montar o array de retorno com os pacientes
        $array_retorno = array();

        foreach($retorno['content'] as $paciente){
            $array = array();
            $array['profissional_id'] = $paciente['profissional_id'];
            $array['profissional_nome'] = $paciente['nome'];
            $array_retorno[] = $array;
        }

        return $array_retorno;
    }

    public function get_nome_paciente($paciente_id){
        $parametros = [
            'paciente_id' => $paciente_id,
            'photo' => true,
        ];
        $apiUrl = "https://api.feegow.com/v1/api/patient/search?".http_build_query($parametros);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
        }
        curl_close($ch);

        return $retorno;

    }

    public function get_nascimento_paciente($paciente_id){
        $parametros = [
            'paciente_id' => $paciente_id,
        ];
        $apiUrl = "https://api.feegow.com/v1/api/patient/search?".http_build_query($parametros);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
        }
        curl_close($ch);

        if (isset($retorno['content']['nascimento'])) {
            return $retorno['content']['nascimento'];
        }

        return "";

    }

    public function register_aplicacao($procedimento, $procedimento_id, $array_aplicacao = null){

        if($procedimento->clinica_id == '5'){
            $local_id = 2;
        }
        elseif($procedimento->clinica_id == '6'){
            $local_id = 6;
        }
        else{
            $local_id = 1;
        }

        $notas = "";
        if($array_aplicacao){
            foreach($array_aplicacao as $aplicacao_id){
                $aplicacao = Aplicacao::where('id', $aplicacao_id)->first();
                $notas .= ", ".$aplicacao->medicamento->nome." ".$aplicacao->quantidade." ".$aplicacao->medicamento->unidade."(s)";
            }
            $notas = substr($notas,2);
        }

        $parametros = [
            'local_id' => $local_id,
            'paciente_id' => $procedimento->paciente->paciente_id_feegow,
            'profissional_id' => 0,
            'especialidade_id' => 0,
            'procedimento_id' => $procedimento_id,
            'data' => date('d-m-Y'),
            'horario' => date('H:i:s' , strtotime('+5 minutes')),
            'valor' => 0,
            'plano' => 0,
            'notas' => $notas,
        ];

        $apiUrl = "https://api.feegow.com/v1/api/appoints/new-appoint?".http_build_query($parametros);

        $ch = curl_init($apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parametros));

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($response);

        if(isset($response->success) && $response->success){
            $agendamento_id = $response->content->agendamento_id;

            $parametros = [
                'AgendamentoID' => $agendamento_id,
                'StatusID' => '3',
                'Obs' => 'Informação enviada pelo sistema',
            ];

            $apiUrl = "https://api.feegow.com/v1/api/appoints/statusUpdate?".http_build_query($parametros);

            $ch = curl_init($apiUrl);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parametros));

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "x-access-token: $this->token",
                "Content-Type: application/json"
            ]);

            $response = curl_exec($ch);

            curl_close($ch);
        }

        //vamos enviar os arquivos que não foram enviados ainda para a feegow
        $procedimentos = Procedimento::where('codigo', $procedimento->codigo)->get();
        $in = array();
        foreach($procedimentos as $proc){
            $in[] = $proc->id;
        }

        $arquivos = ProcedimentoAnexo::where('enviado_feegow','Não')
        ->whereIn('procedimento_id', $in)->get();

        foreach($arquivos as $arquivo){
            $file = public_path('procedimentos/'.$arquivo->procedimento_id."/anexos/".$arquivo->anexo);
            $mime = \Illuminate\Support\Facades\File::mimeType($file);
            $data = \Illuminate\Support\Facades\File::get($file);
            $base64 = base64_encode($data);
            $base64Data = "data:{$mime};base64,{$base64}";

            //$base64Data = base64_encode(file_get_contents($file));

            $parametros = [
                'paciente_id' => $procedimento->paciente->paciente_id_feegow,
                'base64_file' => $base64Data,
                'arquivo_descricao' => 'Anexo do procedimento de codigo '.$procedimento->codigo,
            ];

            //dd($parametros);

            $apiUrl = "https://api.feegow.com/v1/api/patient/upload-base64?".http_build_query($parametros);

            $ch = curl_init($apiUrl);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parametros));

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "x-access-token: $this->token",
                "Content-Type: application/json"
            ]);

            $response = curl_exec($ch);

            curl_close($ch);

            //$arquivo->enviado_feegow = 'Sim';
            $arquivo->save();
        }
    }

    public function get_motivos(){
        $apiUrl = "https://api.feegow.com/v1/api/appoints/status";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
            echo "<pre>";
            print_r($retorno);
            echo "</pre>";
        }
        curl_close($ch);
    }

    public function get_procedimentos(){
        $apiUrl = "https://api.feegow.com/v1/api/procedures/types";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
            echo "<pre>";
            print_r($retorno);
            echo "</pre>";
        }
        curl_close($ch);
    }

    public function get_especialidades(){
        $apiUrl = "https://api.feegow.com/v1/api/specialties/list";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
            echo "<pre>";
            print_r($retorno);
            echo "</pre>";
        }
        curl_close($ch);
    }

    public function get_grupos_procedimento(){
        $apiUrl = "https://api.feegow.com/v1/api/procedures/groups";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
            echo "<pre>";
            print_r($retorno);
            echo "</pre>";
        }
        curl_close($ch);
    }

    public function get_locais(){
        $apiUrl = "https://api.feegow.com/v1/api/company/list-local";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
            echo "<pre>";
            print_r($retorno);
            echo "</pre>";
        }
        curl_close($ch);
    }

    public function get_procedimentos_profissional(){
        $parametros = [
            'profissional_id' => 21,
        ];
        //$apiUrl = "https://api.feegow.com/v1/api/procedures/list?".http_build_query($parametros);
        $apiUrl = 'https://api.feegow.com/v1/api/procedures/professional-list-procedures/22';
        //$apiUrl = "http://localhost:8000/api/procedures/list?tipo=P&profissional_id=22";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Define explicitamente o método GET (opcional)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        dd($response);

        $retorno = json_decode($response, true);

        //dd($response);
        echo "<pre>";
        print_r($retorno['content']);
        echo "</pre>";
        die();

        if (curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        } else {
            $retorno = json_decode($response, true);
        }

        return $retorno['content']['nascimento'];

    }

}
