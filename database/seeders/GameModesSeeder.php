<?php

namespace Database\Seeders;

use App\Models\Games\Shared\GameMode\GameModes;
use Illuminate\Database\Seeder;
use BradieTilley\Snowflake\Snowflake;

class GameModesSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar los modos de juego.
     */
    public function run(): void
    {
        $snowflake = new Snowflake();
        $now = now();

        $gameModes = [
            ['description' => 'Online',  'is_active' => true],
            ['description' => 'Offline', 'is_active' => true],
            ['description' => 'Mixto',   'is_active' => true],
            ['description' => 'Demo',    'is_active' => true],
        ];

        // Agregar ID Snowflake y timestamps
        $gameModes = array_map(fn($mode) => array_merge($mode, [
            'id' => $snowflake->id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]), $gameModes);

        GameModes::insert($gameModes);
    }
}
