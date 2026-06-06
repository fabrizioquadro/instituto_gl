<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$affected = \DB::update("UPDATE procedimentos SET user_id_aplicacao = user_id_cadastro WHERE situacao = 'Aplicado' AND user_id_aplicacao IS NULL");
echo "Foram corrigidos $affected procedimentos que estavam sem identificação.\n";
