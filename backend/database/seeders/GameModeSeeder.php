<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Games\Shared\GameMode\GameMode;

class GameModeSeeder extends Seeder
{
    public function run(): void
    {
        $gameModes = [
            ['description' => 'Online',  'is_active' => true],
            ['description' => 'Offline', 'is_active' => true],
            ['description' => 'Mixto',   'is_active' => true],
            ['description' => 'Demo',    'is_active' => true],
        ];

        // Insertar o actualizar cada modo de forma idempotente
        foreach ($gameModes as $mode) {
            GameMode::updateOrCreate(
                ['description' => $mode['description']],
                ['is_active' => $mode['is_active']]
            );
        }
    }
}
