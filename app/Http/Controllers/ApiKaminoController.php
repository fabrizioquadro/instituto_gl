<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paciente;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ApiKaminoController extends Controller
{
    public function atualiza_pacientes_feegow(){
        //primeiramente vamos atualizar os nossos pacientes da feegow
        $api_feegow = new ApiFlegowController();
        $pacientes = $api_feegow->get_pacientes_limit(1000,14000);

        $contador = 0;
        foreach($pacientes as $linha){
            $contador++;
            $retorno = $api_feegow->get_nome_paciente($linha['paciente_id']);
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
                        Paciente::create($dados_import);
                    }
                }
            }
        }
        echo $contador;
    }

    public function gera_xlsx_kamino(){
        $path = public_path('kamino/ImportacaoPessoas.xlsx');

        // Carrega o arquivo
        $spreadsheet = IOFactory::load($path);

        // Pega a primeira sheet
        $sheet = $spreadsheet->getSheet(0);

        // Descobre a última linha usada
        $ultimaLinha = $sheet->getHighestRow();

        $pacientes = Paciente::whereNotNull('cpf')->offset(12000)->limit(3000)->get();

        // Começa a escrever na próxima linha vazia
        $linhaAtual = $ultimaLinha + 1;

        foreach($pacientes as $paciente){
            $linha = [
                $paciente->cpf,
                '',
                '',
                '',
                $paciente->nm_paciente,
                $paciente->nm_paciente,
                $paciente->dt_nascimento,
                $paciente->endereco,
                $paciente->numero,
                $paciente->complemento,
                $paciente->bairro,
                $paciente->cep,
                $paciente->cidade,
                $paciente->estado,
                '',
                '',
                '',
                $paciente->email,
                $paciente->telefone,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Sim',
                '',
                '',
                '',
                '',
                $paciente->paciente_id_feegow,
            ];
            $sheet->fromArray($linha, null, 'A' . $linhaAtual);
            $linhaAtual++;
        }

        // Salva o arquivo (sobrescrevendo ou criando outro)
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);
    }

    public function integra_clientes(){
        $api_feegow = new ApiFlegowController();

        $pacientes = $api_feegow->get_pacientes_limit(500,0);

        foreach($pacientes as $paciente){
            $retorno = $api_feegow->get_nome_paciente($paciente['paciente_id']);

            //vamos verificar os dados obrigatorios
            if($retorno['success'] == true){
                $dados_paciente = $retorno['content'];
                if((isset($dados_paciente['nome']) || isset($dados_paciente['nome_social'])) && isset($dados_paciente['documentos']['cpf'])){
                    $nome = isset($dados_paciente['nome']) ? $dados_paciente['nome'] : $dados_paciente['nome_social'];
                    $dados_import = [
                        'Nome' => $nome,
                        'CPFCNPJ' => $dados_paciente['documentos']['cpf'],
                        'Cliente' => true,
                    ];
                    $retorno = $this->import_cliente(json_encode($dados_import));
                    echo $retorno."<br>";
                }
            }
        }
    }

    public function import_cliente($dados){
        sleep(1);
        $client = new \GuzzleHttp\Client();

        $response = $client->request('POST', 'https://institutogl.kamino.tech/api/pessoa/grava',
            [
                'body' => $dados,
                'headers' => [
                    'App' => 'bd396410-d4b5-477a-add7-c0afe9a445f3',
                    'CN' => 'InstitutoGL5231',
                    'Hash' => 'gIJCSkZEQD46gkSARTpER0d+On6Cgkc6gT5+hYRKfkRERYVChUaEPkqASYQ6gT5FgDpER0dKOn5CQX46RIFESUpHfoWAREpJiY+Wl4mXmZeRho1FQUJAQQ==',
                    'IDUsr' => '2',
                    'Usr' => 'f6e09b8e-c05b-4779-a32a-4c4897afb498',
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                 ],
            ]
        );

        return $response->getBody();
    }
}
