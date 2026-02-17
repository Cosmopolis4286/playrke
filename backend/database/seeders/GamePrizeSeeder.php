<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Games\Common\Utils\GamePrize;
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
            ['code' => 'FLHE', 'description' => 'Cartón Lleno', 'game_id' => $gameId, 'status' => true],
            ['code' => 'SRL', 'description' => 'Serial', 'game_id' => $gameId, 'status' => true],
            ['code' => 'SONG', 'description' => 'Canto', 'game_id' => $gameId, 'status' => true],
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
