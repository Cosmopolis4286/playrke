<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run() : void
    {
        // Debe reflejar exactamente las claves raíz de
        // SidebarMenuService::$menuStructure — son el punto de unión entre
        // esta tabla y el árbol de menú.
        $modules = [
            ['key' => 'dashboard',    'label' => 'Tablero'],
            ['key' => 'games_bingo',  'label' => 'Bingo'],
            ['key' => 'games_trivia', 'label' => 'Trivia'],
            ['key' => 'business',     'label' => 'Negocio'],
            ['key' => 'wallet',       'label' => 'Billetera'],
            ['key' => 'marketplace',  'label' => 'Marketplace'],
            ['key' => 'users',        'label' => 'Usuarios'],
            ['key' => 'system',       'label' => 'Sistema'],
        ];

        foreach ($modules as $data) {
            Module::updateOrCreate(
                ['key' => $data['key']],
                ['label' => $data['label']],
            );
        }

        $this->command->info('✔ ModuleSeeder: ' . count($modules) . ' módulos procesados.');
    }
}
