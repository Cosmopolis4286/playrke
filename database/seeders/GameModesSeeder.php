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
            ['description' => 'Online', 'status' => 1],
            ['description' => 'Offline', 'status' => 1],
            ['description' => 'Mixto', 'status' => 1],
        ];

        // Inserta todos los registros de manera masiva
        GameModes::insert($gameModes);
    }
}
