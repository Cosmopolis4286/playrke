<?php

namespace App\Console\Commands;

use Database\Seeders\PlatformUserSeeder;
use Illuminate\Console\Command;

class SetupPlatformUser extends Command
{
    protected $description = 'Crea el usuario sistema playrke_platform y sus wallets por moneda';
    protected $signature = 'platform:setup-user';

    public function handle() : int
    {
        $this->info('Configurando usuario de plataforma...');
        $this->call('db:seed', ['--class' => PlatformUserSeeder::class]);
        $this->info('Listo.');

        return self::SUCCESS;
    }
}
