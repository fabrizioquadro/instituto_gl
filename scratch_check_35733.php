<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = \App\Models\Procedimento::with('aplicacaos')->find(35733);
echo json_encode($p->toArray(), JSON_PRETTY_PRINT);
