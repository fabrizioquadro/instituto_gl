<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return $retorno['content']['nome'];

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

        return $retorno['content']['nascimento'];

    }

    public function register_aplicacao($procedimento){
        $parametros = [
            'local_id' => 1,
            'paciente_id' => $procedimento->paciente->paciente_id_feegow,
            'profissional_id' => 0,
            'especialidade_id' => 0,
            'procedimento_id' => 52,
            'data' => date('d-m-Y'),
            'horario' => date('H:i:s'),
            'valor' => 0,
            'plano' => 0,
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

        echo "<pre>";
        print_r($response);
        echo "</pre>";
    }

}
