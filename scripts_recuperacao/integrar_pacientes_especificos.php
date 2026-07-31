<?php
/**
 * Script para verificar/integrar pacientes específicos da Feegow
 * 
 * Uso: php scripts_recuperacao/integrar_pacientes_especificos.php [id1 id2 ...]
 * Exemplos:
 *   php scripts_recuperacao/integrar_pacientes_especificos.php 17882
 *   php scripts_recuperacao/integrar_pacientes_especificos.php 17870 17892 17877
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Paciente;
use App\Http\Controllers\ApiFlegowController;

$api = new ApiFlegowController();

if (isset($argv[1])) {
    $ids = array_slice($argv, 1);
    // Remover valores não numéricos
    $ids = array_values(array_filter($ids, 'is_numeric'));
    $ids = array_map('intval', $ids);
} else {
    $ids = [17870, 17892, 17877, 17747];
}

echo "=== VERIFICANDO PACIENTES FEEGOW ===\n\n";

foreach ($ids as $id) {
    echo "--- ID $id ---\n";
    $paciente = Paciente::where('paciente_id_feegow', $id)->first();

    if ($paciente) {
        echo "JÁ EXISTE: {$paciente->nm_paciente} (local id: {$paciente->id})\n";
        continue;
    }

    echo "NÃO EXISTE. Buscando na Feegow...\n";
    $retorno = $api->get_nome_paciente($id);

    if (!isset($retorno['success']) || $retorno['success'] !== true) {
        echo "  NÃO ENCONTRADO na Feegow!\n\n";
        continue;
    }

    $dados = $retorno['content'];

    if (!isset($dados['nome']) && !isset($dados['nome_social'])) {
        echo "  Sem nome disponível, pulando\n\n";
        continue;
    }

    $nome = isset($dados['nome']) ? $dados['nome'] : $dados['nome_social'];

    // Converte data de nascimento
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
        'paciente_id_feegow' => $dados['id'],
        'endereco' => $dados['endereco'] ?? null,
        'numero' => $dados['numero'] ?? null,
        'complemento' => $dados['complemento'] ?? null,
        'bairro' => $dados['bairro'] ?? null,
        'cidade' => $dados['cidade'] ?? null,
        'estado' => $dados['estado'] ?? null,
        'cep' => $dados['cep'] ?? null,
        'telefone' => $telefones,
        'email' => $email,
        'integrado_kamino' => 'Não',
    ];

    try {
        Paciente::create($dados_import);
        echo "  INTEGRADO ✓: $nome\n";
    } catch (\Exception $e) {
        echo "  ERRO ao integrar: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "=== RESUMO FINAL ===\n";
foreach ($ids as $id) {
    $paciente = Paciente::where('paciente_id_feegow', $id)->first();
    if ($paciente) {
        echo "ID $id: EXISTE -> {$paciente->nm_paciente}\n";
    } else {
        echo "ID $id: NÃO EXISTE\n";
    }
}
