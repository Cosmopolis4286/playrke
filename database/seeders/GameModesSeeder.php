<?php

namespace Database\Seeders;

use App\Models\Games\Shared\GameMode\GameModes;
use Illuminate\Database\Seeder;

class GameModesSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar los modos de juego.
     *
     * @return void
     */
    public function run(): void
    {
        $gameModes = [
            ['description' => 'Online', 'is_active' => true],
            ['description' => 'Offline', 'is_active' => true],
            ['description' => 'Mixto', 'is_active' => true],
            ['description' => 'Demo', 'is_active' => true],
        ];

        // Inserta todos los registros de manera masiva
        GameModes::insert($gameModes);
    }
}
