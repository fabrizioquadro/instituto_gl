<?php
/**
 * Script de Sincronização de Pacientes da Feegow (IDs >= 17000)
 * 
 * Este script:
 * 1. Busca todos os pacientes da Feegow via API (paginação automática)
 * 2. Filtra aqueles com patient_id >= 17000
 * 3. Verifica quais já existem no banco local
 * 4. Cria os que não existem (buscando detalhes individualmente)
 * 5. Gera um relatório completo ao final
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Paciente;
use App\Http\Controllers\ApiFlegowController;

echo "=============================================\n";
echo " SINCRONIZAÇÃO DE PACIENTES FEEGOW (ID>=17000)\n";
echo "=============================================\n\n";

$api = new ApiFlegowController();
$inicio_id = 17000;

// ==============================================
// PASSO 1: Buscar todos os IDs de pacientes da Feegow
// ==============================================
echo "[1/4] Buscando pacientes da Feegow (paginação automática)...\n";
$todos_pacientes_feegow = $api->get_pacientes();
echo "Total de pacientes retornados pela Feegow: " . count($todos_pacientes_feegow) . "\n\n";

// ==============================================
// PASSO 2: Filtrar por ID >= 17000
// ==============================================
echo "[2/4] Filtrando pacientes com ID >= $inicio_id...\n";
$pacientes_filtrados = [];
foreach ($todos_pacientes_feegow as $p) {
    if ($p['paciente_id'] >= $inicio_id) {
        $pacientes_filtrados[$p['paciente_id']] = $p;
    }
}
echo "Pacientes na Feegow com ID >= $inicio_id: " . count($pacientes_filtrados) . "\n\n";

// ==============================================
// PASSO 3: Verificar quais já existem no banco local
// ==============================================
echo "[3/4] Verificando quais já existem no banco local...\n";
$ids_feegow_local = Paciente::where('paciente_id_feegow', '>=', $inicio_id)
    ->pluck('paciente_id_feegow')
    ->toArray();
$ids_locais_set = array_flip($ids_feegow_local);

$existentes = 0;
$para_criar = [];
foreach ($pacientes_filtrados as $id => $paciente) {
    if (isset($ids_locais_set[$id])) {
        $existentes++;
    } else {
        $para_criar[$id] = $paciente;
    }
}

echo "Já existentes no banco local: $existentes\n";
echo "Novos pacientes a criar: " . count($para_criar) . "\n\n";

// ==============================================
// PASSO 4: Buscar detalhes e criar os novos pacientes
// ==============================================
echo "[4/4] Buscando detalhes e criando novos pacientes...\n";

$criados = 0;
$erros = 0;
$nao_encontrados = 0;
$total_para_criar = count($para_criar);
$contador = 0;

foreach ($para_criar as $id => $dados_basicos) {
    $contador++;
    echo "[$contador/$total_para_criar] ID $id ({$dados_basicos['paciente_nome']})... ";

    $retorno = $api->get_nome_paciente($id);

    if (!isset($retorno['success']) || $retorno['success'] !== true) {
        echo "NÃO ENCONTRADO na API\n";
        $nao_encontrados++;
        continue;
    }

    $dados = $retorno['content'];

    if (!isset($dados['nome']) && !isset($dados['nome_social'])) {
        echo "Sem nome disponível, pulando\n";
        $nao_encontrados++;
        continue;
    }

    $nome = isset($dados['nome']) ? $dados['nome'] : $dados['nome_social'];

    // Converte data de nascimento (formato Feegow: dd-mm-yyyy -> yyyy-mm-dd)
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
        echo "CRIADO ✓\n";
        $criados++;
    } catch (\Exception $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
        $erros++;
    }
}

// ==============================================
// RELATÓRIO FINAL
// ==============================================
echo "\n=============================================\n";
echo "            RELATÓRIO FINAL\n";
echo "=============================================\n";
echo "Total de pacientes na Feegow:        " . count($todos_pacientes_feegow) . "\n";
echo "Com ID >= $inicio_id na Feegow:      " . count($pacientes_filtrados) . "\n";
echo "Já existentes no banco local:        $existentes\n";
echo "Novos pacientes criados:             $criados\n";
echo "Não encontrados na Feegow (sumiu):   $nao_encontrados\n";
echo "Erros:                               $erros\n";
echo "---------------------------------------------\n";
echo "Total no banco local agora:          " . Paciente::count() . "\n";
echo "Com ID >= $inicio_id agora:          " . Paciente::where('paciente_id_feegow', '>=', $inicio_id)->count() . "\n";
echo "=============================================\n";
