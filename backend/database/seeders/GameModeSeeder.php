<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shared\GameMode\GameMode;

class GameModeSeeder extends Seeder
{
    public function run(): void
    {
        $gameModes = [
            ['description' => 'Online',  'status' => true],
            ['description' => 'Offline', 'status' => true],
            ['description' => 'Mixto',   'status' => true],
            ['description' => 'Demo',    'status' => true],
        ];

        // Insertar o actualizar cada modo de forma idempotente
        foreach ($gameModes as $mode) {
            GameMode::updateOrCreate(
                ['description' => $mode['description']],
                ['status' => $mode['status']]
            );
        }
    }
}
