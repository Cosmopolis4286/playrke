<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Shared\Utils\GamePrize;
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
            ['description' => 'Cartón Lleno', 'code' => 'FLHE', 'game_id' => $gameId, 'status' => true],
            ['description' => 'Serial',      'code' => 'SRL',  'game_id' => $gameId, 'status' => true],
            ['description' => 'Canto',       'code' => 'SONG', 'game_id' => $gameId, 'status' => true],
        ];

        // Insertar o actualizar cada premio de forma idempotente
        foreach ($prizes as $prize) {
            GamePrize::updateOrCreate(
                ['code' => $prize['code'], 'game_id' => $prize['game_id']],
                ['description' => $prize['description'], 'status' => $prize['status']]
            );
        }
    }
}
