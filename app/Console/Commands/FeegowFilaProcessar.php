<?php

namespace App\Console\Commands;

use App\Http\Controllers\ApiFlegowController;
use Illuminate\Console\Command;

class FeegowFilaProcessar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feegow:fila {--limite=20 : Quantidade máxima de registros processados por execução}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa a fila de envio para a Feegow (prescrições V2) — agendar para rodar a cada minuto via cron';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $api = new ApiFlegowController();
        $processados = $api->processar_fila((int) $this->option('limite'));
        $this->info("Feegow: {$processados} registro(s) processado(s).");
        return 0;
    }
}
