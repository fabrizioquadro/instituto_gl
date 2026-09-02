<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aplicacao;
use App\Models\Procedimento;
use App\Models\ProcedimentoAnexo;
use App\Models\Anexo;
use App\Models\FeegowFila;
use App\Models\Prescricao;
use App\Models\PrescricaoSemana;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
        $array_retorno = array();
        $offset = 0;
        $limit = 5000;

        while(true){
            $parametros = [
                'limit' => $limit,
                'offset' => $offset,
            ];
            if($ultima_atualizacao){
                $parametros['alterado_em'] = $ultima_atualizacao;
            }

            $apiUrl = "https://api.feegow.com/v1/api/patient/list?".http_build_query($parametros);
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "x-access-token: $this->token",
                "Content-Type: application/json"
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Erro na requisição: ' . curl_error($ch);
                break;
            } else {
                $retorno = json_decode($response, true);
            }
            curl_close($ch);

            if(!isset($retorno['content']) || empty($retorno['content'])){
                break;
            }

            foreach($retorno['content'] as $paciente){
                $array = array();
                $array['paciente_id'] = $paciente['patient_id'];
                $array['paciente_nome'] = $paciente['nome'];
                $array['dt_nascimento'] = $paciente['nascimento'];
                $array_retorno[] = $array;
            }

            $offset += $limit;
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
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

    // ============================================================
    // FILA DE ENVIO PARA A FEEGOW (PRESCRIÇÕES V2)
    // ============================================================

    private function postFeegow($apiUrl, $parametros)
    {
        $ch = curl_init($apiUrl . '?' . http_build_query($parametros));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-access-token: $this->token",
            "Content-Type: application/json"
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($errno) {
            throw new \Exception('Erro de conexão com a Feegow (' . $errno . '): ' . $error);
        }
        return $response;
    }

    /**
     * Monta a nota completa da aplicação de uma semana de prescrição (V2).
     */
    private function montar_notas_prescricao($semana)
    {
        $p = $semana->prescricao;
        $paciente = $p->paciente;
        $fmt = function ($v) {
            return $v ? date('d/m/Y H:i:s', strtotime($v)) : '-';
        };
        $fmtData = function ($v) {
            return $v ? date('d/m/Y', strtotime($v)) : '-';
        };

        $linhas = [];
        $linhas[] = 'PRESCRIÇÃO #' . $p->id . ' - SEMANA ' . $semana->nr_semana;
        $linhas[] = 'Paciente: ' . ($paciente->nm_paciente ?? '-');
        $linhas[] = 'Data Prevista: ' . $fmtData($semana->data_prevista);
        $linhas[] = 'Chegada: ' . $fmt($semana->dt_hr_chegada);
        $linhas[] = 'Início (Atendimento): ' . $fmt($semana->dt_hr_atendimento);
        $linhas[] = 'Aplicação (Conclusão): ' . $fmt($semana->dt_hr_finalizacao);
        $aplicador = $semana->userAplicacao ? $semana->userAplicacao->nome : ($semana->user_id_aplicacao ? 'usuário #' . $semana->user_id_aplicacao : '-');
        $linhas[] = 'Aplicado por: ' . $aplicador;
        $linhas[] = 'Situação da Semana: ' . $semana->situacao;
        $linhas[] = 'Obs: ' . ($semana->obs ?: '-');
        $linhas[] = '';

        $aplicados = $semana->medicamentos->filter(fn($m) => $m->situacao == 'Aplicada');
        $pendentes = $semana->medicamentos->filter(fn($m) => $m->situacao == 'Pendente');

        $linhas[] = 'MEDICAMENTOS APLICADOS (' . $aplicados->count() . '):';
        if ($aplicados->count() == 0) {
            $linhas[] = '  (nenhum)';
        }
        foreach ($aplicados as $m) {
            $nome = $m->medicamento ? $m->medicamento->nome : ('ID ' . $m->medicamento_id);
            $qtd = rtrim(rtrim(number_format((float) $m->quantidade, 2, ',', '.'), '0'), ',');
            $unidade = $m->medicamento ? $m->medicamento->unidade : '-';
            $lotes = $m->lotes->pluck('lote')->unique()->implode(', ');
            $cods = $m->lotes->pluck('codigo_barras')->unique()->implode(', ');
            $aplicMed = $m->userAplicacao ? $m->userAplicacao->nome : ($m->user_id_aplicacao ? 'usuário #' . $m->user_id_aplicacao : '-');
            $linhas[] = '  - ' . $nome . ' | Qtd: ' . $qtd . ' ' . $unidade . ' | Lote: ' . ($lotes ?: '-') . ' | Código: ' . ($cods ?: '-') . ' | Aplicado por: ' . $aplicMed;
        }

        $linhas[] = 'MEDICAMENTOS PENDENTES (' . $pendentes->count() . '):';
        if ($pendentes->count() == 0) {
            $linhas[] = '  (nenhum)';
        }
        foreach ($pendentes as $m) {
            $nome = $m->medicamento ? $m->medicamento->nome : ('ID ' . $m->medicamento_id);
            $qtd = rtrim(rtrim(number_format((float) $m->quantidade, 2, ',', '.'), '0'), ',');
            $linhas[] = '  - ' . $nome . ' | Qtd: ' . $qtd;
        }

        return implode("\n", $linhas);
    }

    /**
     * Enfileira o envio da aplicação de uma semana de prescrição (V2) para a Feegow.
     * NÃO chama a Feegow aqui — só grava na fila. O robô processa depois.
     */
    public function enfileirar_aplicacao_prescricao($semana_id)
    {
        $semana = PrescricaoSemana::with([
            'prescricao.paciente',
            'medicamentos.medicamento',
            'medicamentos.lotes',
            'userAplicacao',
        ])->find($semana_id);

        if (!$semana || !$semana->prescricao || !$semana->prescricao->paciente) {
            return;
        }

        $clinica_id = $semana->prescricao->clinica_id;
        $local_id = ($clinica_id == 5) ? 2 : (($clinica_id == 6) ? 6 : 1);

        // data/hora real da aplicação (usa a finalização da semana)
        $dt_aplic = $semana->dt_hr_finalizacao ? date('d-m-Y H:i:s', strtotime($semana->dt_hr_finalizacao)) : date('d-m-Y H:i:s');
        [$data, $horario] = explode(' ', $dt_aplic);

        $payload = [
            'prescricao_id' => $semana->prescricao_id,
            'semana_id' => $semana->id,
            'evento' => 'aplicacao',
            'procedimento_id' => 52,
            'local_id' => $local_id,
            'paciente_id_feegow' => $semana->prescricao->paciente->paciente_id_feegow,
            'data' => $data,
            'horario' => $horario,
            'notas' => $this->montar_notas_prescricao($semana),
        ];

        return FeegowFila::create([
            'prescricao_id' => $semana->prescricao_id,
            'prescricao_semana_id' => $semana->id,
            'evento' => 'aplicacao',
            'procedimento_id' => 52,
            'payload' => $payload,
            'situacao' => 'Pendente',
            'tentativas' => 0,
            'proxima_tentativa' => null,
        ]);
    }

    /**
     * Processa a fila de envio para a Feegow (chamado pelo robô a cada minuto).
     */
    public function processar_fila($limite = 20)
    {
        $rows = FeegowFila::where('situacao', 'Pendente')
            ->where(function ($q) {
                $q->whereNull('proxima_tentativa')->orWhere('proxima_tentativa', '<=', now());
            })
            ->orderBy('id')
            ->limit($limite)
            ->get();

        foreach ($rows as $row) {
            try {
                $this->enviar_registro_feegow($row);
                $row->situacao = 'Enviado';
                $row->enviado_em = now();
                $row->erro = null;
                $row->ultima_tentativa = now();
                $row->save();
            } catch (\Throwable $e) {
                $row->tentativas = (int) $row->tentativas + 1;
                $row->erro = substr($e->getMessage(), 0, 500);
                $row->proxima_tentativa = now()->addMinutes($this->backoff_minutos($row->tentativas));
                $row->ultima_tentativa = now();
                $row->save();
                Log::error('Feegow fila #' . $row->id . ' erro: ' . $e->getMessage());
            }
        }

        return $rows->count();
    }

    private function backoff_minutos($tentativas)
    {
        // 1, 5, 15, 60, 180, 360, 720, 1440 minutos
        $escalas = [1, 5, 15, 60, 180, 360, 720, 1440];
        $idx = min((int) $tentativas - 1, count($escalas) - 1);
        return $escalas[$idx];
    }

    private function enviar_registro_feegow($row)
    {
        $payload = is_array($row->payload) ? $row->payload : [];

        $parametros = [
            'local_id' => $payload['local_id'] ?? 1,
            'paciente_id' => $payload['paciente_id_feegow'] ?? 0,
            'profissional_id' => 0,
            'especialidade_id' => 0,
            'procedimento_id' => $payload['procedimento_id'] ?? 52,
            'data' => $payload['data'] ?? date('d-m-Y'),
            'horario' => $payload['horario'] ?? date('H:i:s'),
            'valor' => 0,
            'plano' => 0,
            'notas' => $payload['notas'] ?? '',
        ];

        $response = $this->postFeegow('https://api.feegow.com/v1/api/appoints/new-appoint', $parametros);
        $decoded = json_decode($response);

        if (!isset($decoded->success) || !$decoded->success) {
            $msg = isset($decoded->message) ? $decoded->message : 'resposta sem sucesso';
            throw new \Exception('Feegow new-appoint: ' . $msg . ' | resp: ' . substr($response, 0, 300));
        }

        $agendamento_id = $decoded->content->agendamento_id ?? null;

        // status update (não crítico — se falhar não bloqueia o envio do agendamento)
        if ($agendamento_id) {
            try {
                $this->postFeegow('https://api.feegow.com/v1/api/appoints/statusUpdate', [
                    'AgendamentoID' => $agendamento_id,
                    'StatusID' => '3',
                    'Obs' => 'Informação enviada pelo sistema',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Feegow statusUpdate falhou para fila #' . $row->id . ': ' . $e->getMessage());
            }
        }

        // anexos (pedido médico) pendentes de envio (não crítico)
        try {
            $this->enviar_anexos_prescricao($payload['prescricao_id'] ?? $row->prescricao_id);
        } catch (\Throwable $e) {
            Log::warning('Feegow envio de anexos falhou para fila #' . $row->id . ': ' . $e->getMessage());
        }
    }

    private function enviar_anexos_prescricao($prescricao_id)
    {
        if (!$prescricao_id) {
            return;
        }
        $prescricao = Prescricao::with('paciente')->find($prescricao_id);
        if (!$prescricao || !$prescricao->paciente) {
            return;
        }
        $paciente_feegow = $prescricao->paciente->paciente_id_feegow;

        $anexos = Anexo::where('prescricao_id', $prescricao_id)
            ->where('tipo', 'prescricao_medica')
            ->where('enviado_feegow', 'Não')
            ->get();

        foreach ($anexos as $anexo) {
            $file = public_path('prescricoes/' . $prescricao_id . '/anexos/' . $anexo->arquivo);
            if (!is_file($file)) {
                continue;
            }
            $mime = File::mimeType($file);
            $base64 = base64_encode(File::get($file));
            $this->postFeegow('https://api.feegow.com/v1/api/patient/upload-base64', [
                'paciente_id' => $paciente_feegow,
                'base64_file' => 'data:' . $mime . ';base64,' . $base64,
                'arquivo_descricao' => 'Anexo (pedido médico) da prescrição ' . $prescricao_id,
            ]);
            $anexo->enviado_feegow = 'Sim';
            $anexo->save();
        }
    }

}
