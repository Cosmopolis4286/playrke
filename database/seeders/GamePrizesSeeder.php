<?php

namespace Database\Seeders;

use App\Models\Games\Shared\Games;
use App\Models\Games\Shared\Utils\GamePrizes;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class GamePrizesSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el ID del juego 'BINGO'
        $gameId = Games::where('name', 'BINGO')->value('id');

        if (!$gameId) {
            $this->command->warn("Juego 'BINGO' no encontrado. Seeder GamePrizes omitido.");
            return;
        }

        $prizes = [
            ['description' => 'Cartón Lleno', 'code' => 'FLHE', 'game_id' => $gameId, 'is_active' => true],
            ['description' => 'Serial',      'code' => 'SRL',  'game_id' => $gameId, 'is_active' => true],
            ['description' => 'Canto',       'code' => 'SONG', 'game_id' => $gameId, 'is_active' => true],
        ];

        // Crear instancia de Snowflake
        $snowflake = new Snowflake();

        // Agregar ID Snowflake a cada registro
        $prizes = array_map(function ($prize) use ($snowflake) {
            $prize['id'] = $snowflake->id();
            $prize['created_at'] = now();
            $prize['updated_at'] = now();
            return $prize;
        }, $prizes);

        // Inserción masiva
        GamePrizes::insert($prizes);
    }
}
