<?php
/**
 * Script para atualizar o paciente com paciente_id_feegow = 17739
 * com os dados atuais da Feegow.
 *
 * Uso:
 *   php scripts_recuperacao/atualizar_paciente_17739.php          -> mostra diferença (dry-run)
 *   php scripts_recuperacao/atualizar_paciente_17739.php --apply   -> aplica a atualização
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Paciente;
use App\Http\Controllers\ApiFlegowController;

const ID_FEEGOW = 17739;

$apply = in_array('--apply', $argv, true);

$api = new ApiFlegowController();
$paciente = Paciente::where('paciente_id_feegow', ID_FEEGOW)->first();

echo "=== PACIENTE FEEGOW ID: ".ID_FEEGOW." ===\n\n";

if (!$paciente) {
    echo "ATENÇÃO: paciente com paciente_id_feegow = ".ID_FEEGOW." NÃO EXISTE no banco local.\n";
    echo "Este script foi desenhado para ATUALIZAR um registro existente.\n";
    exit(1);
}

echo "REGISTRO LOCAL ATUAL (id local: {$paciente->id}):\n";
echo json_encode($paciente->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";

// Busca dados atuais na Feegow
$retorno = $api->get_nome_paciente(ID_FEEGOW);

if (!isset($retorno['success']) || $retorno['success'] !== true) {
    echo "ERRO: não foi possível buscar o paciente na Feegow.\n";
    echo "Resposta: ".json_encode($retorno, JSON_UNESCAPED_UNICODE)."\n";
    exit(1);
}

$dados = $retorno['content'];
echo "DADOS ATUAIS NA FEEGOW:\n";
echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";

if (!isset($dados['nome']) && !isset($dados['nome_social'])) {
    echo "ERRO: paciente sem nome/nome_social na Feegow.\n";
    exit(1);
}

$nome = isset($dados['nome']) ? $dados['nome'] : $dados['nome_social'];

// Converte data de nascimento (Feegow vem como Y-m-d -> armazenado como d-m-Y)
$dt_nascimento = null;
if (!empty($dados['nascimento'])) {
    $var = explode('-', $dados['nascimento']);
    if (count($var) === 3) {
        $dt_nascimento = $var[2] . '-' . $var[1] . '-' . $var[0];
    }
}

// Monta telefones
$telefones = null;
if (!empty($dados['telefones'])) {
    $tel = [];
    if (!empty($dados['telefones'][0])) $tel[] = $dados['telefones'][0];
    if (!empty($dados['telefones'][1])) $tel[] = $dados['telefones'][1];
    if (!empty($dados['celulares'][0])) $tel[] = $dados['celulares'][0];
    if (!empty($dados['celulares'][1])) $tel[] = $dados['celulares'][1];
    $telefones = implode(' ', $tel);
}

// Monta email
$email = null;
if (!empty($dados['email'])) {
    $emails = array_filter($dados['email']);
    $email = implode(' ', $emails);
}

$dados_import = [
    'nm_paciente' => $nome,
    'dt_nascimento' => $dt_nascimento,
    'cpf' => $dados['documentos']['cpf'] ?? null,
    'endereco' => $dados['endereco'] ?? null,
    'numero' => $dados['numero'] ?? null,
    'complemento' => $dados['complemento'] ?? null,
    'bairro' => $dados['bairro'] ?? null,
    'cidade' => $dados['cidade'] ?? null,
    'estado' => $dados['estado'] ?? null,
    'cep' => $dados['cep'] ?? null,
    'telefone' => $telefones,
    'email' => $email,
];

echo "=== COMPARAÇÃO DE CAMPOS ===\n";
$campos = array_keys($dados_import);
$tem_diferenca = false;
foreach ($campos as $campo) {
    $antes = $paciente->{$campo};
    $depois = $dados_import[$campo];
    $igual = ((string) $antes === (string) $depois) || ($antes === null && $depois === null);
    if (!$igual) {
        $tem_diferenca = true;
    }
    $icone = $igual ? 'OK ' : '>>> ';
    echo sprintf("%-16s %s | local: %-30s | feegow: %s\n", $campo, $icone, var_export($antes, true), var_export($depois, true));
}

echo "\n";

if (!$tem_diferenca) {
    echo "Nenhuma diferença encontrada. O registro local já está atualizado.\n";
    exit(0);
}

if (!$apply) {
    echo "Modo DRY-RUN: nenhuma alteração foi feita.\n";
    echo "Para aplicar, rode: php scripts_recuperacao/atualizar_paciente_17739.php --apply\n";
    exit(0);
}

// Aplica a atualização
try {
    $paciente->update($dados_import);
    $paciente->refresh();
    echo "ATUALIZADO ✓\n\n";
    echo "REGISTRO LOCAL APÓS ATUALIZAÇÃO (id local: {$paciente->id}):\n";
    echo json_encode($paciente->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n";
} catch (\Exception $e) {
    echo "ERRO ao atualizar: " . $e->getMessage() . "\n";
    exit(1);
}
