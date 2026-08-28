<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$name = '2026_08_26_000200_fix_situacao_pendente_prescricao_semanas';

// remove o registro da tabela migrations (evita referência órfã)
$del = DB::table('migrations')->where('migration', $name)->delete();
echo "Registro removido da tabela migrations: $del\n";

// remove o arquivo da migration
$file = __DIR__ . '/database/migrations/' . $name . '.php';
if (file_exists($file)) {
    unlink($file);
    echo "Arquivo removido: $file\n";
} else {
    echo "Arquivo não encontrado: $file\n";
}
