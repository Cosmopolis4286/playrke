<?php

namespace Database\Seeders;

use App\Models\Games\Game;
use App\Models\Games\Shared\Utils\GamePrize;
use Illuminate\Database\Seeder;

class GamePrizeSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el ID del juego 'BINGO'
        $gameId = Game::where('name', 'BINGO')->value('id');

        if (!$gameId) {
            $this->command->warn("Juego 'BINGO' no encontrado. Seeder GamePrizes omitido.");
            return;
        }

        $prizes = [
            ['description' => 'Cartón Lleno', 'code' => 'FLHE', 'game_id' => $gameId, 'is_active' => true],
            ['description' => 'Serial',      'code' => 'SRL',  'game_id' => $gameId, 'is_active' => true],
            ['description' => 'Canto',       'code' => 'SONG', 'game_id' => $gameId, 'is_active' => true],
        ];

        // Insertar o actualizar cada premio de forma idempotente
        foreach ($prizes as $prize) {
            GamePrize::updateOrCreate(
                ['code' => $prize['code'], 'game_id' => $prize['game_id']],
                ['description' => $prize['description'], 'is_active' => $prize['is_active']]
            );
        }
    }
}
