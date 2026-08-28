<?php
/**
 * Restauração do grupo de procedimentos 2293120260731140250
 *
 * Estado original (confirmado pelos procedimento_logs):
 *   - Procedimentos: situacao = 'Agendado'  (log mostra antigos: {"situacao":"Agendado"} -> novos: {"situacao":"Cancelado"})
 *   - Aplicações:    situacao = 'Aberta'    (medicamentos com aplicacao='Sim' + cancelar_set só cancela não-'Aplicada')
 *
 * Uso:
 *   php scripts_recuperacao/restaurar_grupo_2293120260731140250.php          -> dry-run
 *   php scripts_recuperacao/restaurar_grupo_2293120260731140250.php --apply   -> aplica
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Procedimento;

const CODIGO = '2293120260731140250';
const SITUACAO_PROC_ORIGINAL = 'Agendado';
const SITUACAO_APLICACAO_ORIGINAL = 'Aberta';

$apply = in_array('--apply', $argv, true);

$procedimentos = Procedimento::where('codigo', CODIGO)->orderBy('id')->get();

if ($procedimentos->count() === 0) {
    echo "Nenhum procedimento encontrado para o codigo ".CODIGO."\n";
    exit(1);
}

echo "=== GRUPO ".CODIGO." - PLANO DE RESTAURAÇÃO ===\n\n";

$total_proc_afetados = 0;
$total_aplic_afetadas = 0;

foreach ($procedimentos as $p) {
    $proc_alvo = $p->situacao === 'Cancelado' ? SITUACAO_PROC_ORIGINAL : null;

    $aplicacoes_alvo = [];
    foreach ($p->aplicacaos as $a) {
        if ($a->situacao === 'Cancelada') {
            $aplicacoes_alvo[] = ['id' => $a->id, 'de' => $a->situacao, 'para' => SITUACAO_APLICACAO_ORIGINAL];
        }
    }

    $linha = "ID {$p->id} | nr_proc {$p->nr_procedimento} | situacao '{$p->situacao}'";
    if ($proc_alvo !== null) {
        $linha .= " -> '$proc_alvo' [RESTAURAR]";
        $total_proc_afetados++;
    } else {
        $linha .= " -> sem alteração";
    }
    echo $linha . "\n";

    foreach ($aplicacoes_alvo as $app) {
        echo "   aplicacao id {$app['id']}: '{$app['de']}' -> '{$app['para']}' [RESTAURAR]\n";
        $total_aplic_afetadas++;
    }
}

echo "\nResumo: {$total_proc_afetados} procedimentos e {$total_aplic_afetadas} aplicações a restaurar.\n";

if ($total_proc_afetados === 0 && $total_aplic_afetadas === 0) {
    echo "Nada a fazer - grupo já está restaurado.\n";
    exit(0);
}

if (!$apply) {
    echo "\nModo DRY-RUN: nenhuma alteração feita.\n";
    echo "Para aplicar, rode: php scripts_recuperacao/restaurar_grupo_2293120260731140250.php --apply\n";
    exit(0);
}

// Aplica
echo "\n=== APLICANDO RESTAURAÇÃO ===\n";
$count = 0;
foreach ($procedimentos as $p) {
    if ($p->situacao === 'Cancelado') {
        $p->situacao = SITUACAO_PROC_ORIGINAL;
        $p->save(); // dispara observer -> registra log de auditoria
        echo "Procedimento ID {$p->id}: Cancelado -> ".SITUACAO_PROC_ORIGINAL." ✓\n";
        $count++;
    }
    foreach ($p->aplicacaos as $a) {
        if ($a->situacao === 'Cancelada') {
            $a->situacao = SITUACAO_APLICACAO_ORIGINAL;
            $a->save();
            echo "  Aplicação ID {$a->id}: Cancelada -> ".SITUACAO_APLICACAO_ORIGINAL." ✓\n";
        }
    }
}

echo "\nRestauração concluída ($count procedimentos).\n";

// Status do grupo após restauração
$p = Procedimento::where('codigo', CODIGO)->where('nr_procedimento', '1')->first();
if ($p) {
    echo "Status do grupo agora: {$p->get_st_procedimento()}\n";
}
