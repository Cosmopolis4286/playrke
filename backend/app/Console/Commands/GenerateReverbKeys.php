<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateReverbKeys extends Command
{
    protected $description = 'Genera un APP_ID, APP_KEY y APP_SECRET para Reverb/Soketi';
    protected $signature = 'reverb:generate-keys';

    public function handle()
    {
        // Generar APP_ID numérico entre 100000 y 999999
        $appId = random_int(100000, 999999);

        // Generar APP_KEY y APP_SECRET aleatorios de 32 caracteres alfanuméricos
        $appKey = Str::random(32);
        $appSecret = Str::random(32);

        $this->info('Llaves generadas para Reverb/Soketi:');
        $this->line("REVERB_APP_ID={$appId}");
        $this->line("REVERB_APP_KEY={$appKey}");
        $this->line("REVERB_APP_SECRET={$appSecret}");

        $this->info('Ahora copia estas líneas en tu archivo .env');
    }
}
