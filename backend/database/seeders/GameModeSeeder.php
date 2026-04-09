<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Games\Common\GameMode\GameMode;

class GameModeSeeder extends Seeder
{
    public function run(): void
    {
        $gameModes = [
            ['code' => 'ONLINE', 'label' => 'Online'],
            ['code' => 'OFFLINE', 'label' => 'Offline'],
            ['code' => 'MIXED', 'label' => 'Mixto'],
        ];

        // Insertar o actualizar cada modo de forma idempotente
        foreach ($gameModes as $mode) {
            GameMode::updateOrCreate(
                ['code' => $mode['code']],
                [
                    'label' => $mode['label'],
                    'status'      => 'ACTIVE',
                ]
            );
        }
    }
}
