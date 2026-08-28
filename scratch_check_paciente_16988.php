<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Paciente;
use App\Http\Controllers\ApiFlegowController;

$paciente = Paciente::where('paciente_id_feegow', 16988)->first();

echo "========== DADOS ATUAIS NO BANCO ==========\n";
if ($paciente) {
    echo "id: " . $paciente->id . "\n";
    echo "nm_paciente: " . $paciente->nm_paciente . "\n";
    echo "dt_nascimento: " . $paciente->dt_nascimento . "\n";
    echo "cpf: " . $paciente->cpf . "\n";
    echo "endereco: " . $paciente->endereco . "\n";
    echo "numero: " . $paciente->numero . "\n";
    echo "complemento: " . $paciente->complemento . "\n";
    echo "bairro: " . $paciente->bairro . "\n";
    echo "cidade: " . $paciente->cidade . "\n";
    echo "estado: " . $paciente->estado . "\n";
    echo "cep: " . $paciente->cep . "\n";
    echo "telefone: " . $paciente->telefone . "\n";
    echo "email: " . $paciente->email . "\n";
} else {
    echo "Nenhum paciente local com paciente_id_feegow = 16988\n";
}

echo "\n========== DADOS DA FEEGOW (API) ==========\n";
$api = new ApiFlegowController();
$retorno = $api->get_nome_paciente(16988);

if (isset($retorno['success']) && $retorno['success'] == true) {
    $d = $retorno['content'];
    echo "success: true\n";
    echo "id: " . ($d['id'] ?? '') . "\n";
    echo "nome: " . ($d['nome'] ?? '') . "\n";
    echo "nome_social: " . ($d['nome_social'] ?? '') . "\n";
    echo "nascimento: " . ($d['nascimento'] ?? '') . "\n";
    echo "cpf: " . ($d['documentos']['cpf'] ?? '') . "\n";
    echo "endereco: " . ($d['endereco'] ?? '') . "\n";
    echo "numero: " . ($d['numero'] ?? '') . "\n";
    echo "complemento: " . ($d['complemento'] ?? '') . "\n";
    echo "bairro: " . ($d['bairro'] ?? '') . "\n";
    echo "cidade: " . ($d['cidade'] ?? '') . "\n";
    echo "estado: " . ($d['estado'] ?? '') . "\n";
    echo "cep: " . ($d['cep'] ?? '') . "\n";
    echo "telefones: " . json_encode($d['telefones'] ?? null) . "\n";
    echo "celulares: " . json_encode($d['celulares'] ?? null) . "\n";
    echo "email: " . json_encode($d['email'] ?? null) . "\n";
} else {
    echo "success: false\n";
    echo "retorno completo:\n";
    print_r($retorno);
}
