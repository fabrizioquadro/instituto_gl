<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Paciente;
use App\Http\Controllers\ApiFlegowController;

$paciente_id_feegow = 16988;

$paciente = Paciente::where('paciente_id_feegow', $paciente_id_feegow)->first();
if (!$paciente) {
    echo "ERRO: Nenhum paciente local com paciente_id_feegow = $paciente_id_feegow\n";
    exit(1);
}

echo "DADOS ATUAIS (id local: {$paciente->id})\n";
echo "  nm_paciente: {$paciente->nm_paciente}\n";
echo "  dt_nascimento: {$paciente->dt_nascimento}\n";
echo "  cpf: {$paciente->cpf}\n";
echo "  email: {$paciente->email}\n";
echo "  endereco: {$paciente->endereco}\n\n";

$api = new ApiFlegowController();
$retorno = $api->get_nome_paciente($paciente_id_feegow);

if (!isset($retorno['success']) || $retorno['success'] != true) {
    echo "ERRO: Falha na chamada à API da Feegow.\n";
    print_r($retorno);
    exit(1);
}

$dados_paciente = $retorno['content'];

// ===== Mesma lógica do PacienteSistemaController::atualizar_integracao =====
$nome = isset($dados_paciente['nome']) ? $dados_paciente['nome'] : $dados_paciente['nome_social'];

$dt_nascimento = null;
if ($dados_paciente['nascimento']) {
    $var = explode('-', $dados_paciente['nascimento']);
    $dt_nascimento = $var[2] . '-' . $var[1] . '-' . $var[0];
}

$telefones = null;
if ($dados_paciente['telefones']) {
    $telefones = $dados_paciente['telefones'][0] . " " . $dados_paciente['telefones'][1] . " " . $dados_paciente['celulares'][0] . " " . $dados_paciente['celulares'][1];
}

$email = null;
if ($dados_paciente['email']) {
    $email = $dados_paciente['email'][0] . " " . $dados_paciente['email'][1];
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

echo "DADOS NOVOS (da Feegow)\n";
foreach ($dados_import as $campo => $valor) {
    echo "  $campo: " . ($valor ?: '(vazio)') . "\n";
}

$paciente->update($dados_import);

echo "\n✅ Paciente atualizado com sucesso! (id local: {$paciente->id})\n";
