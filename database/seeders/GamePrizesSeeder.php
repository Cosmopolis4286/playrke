<?php

namespace Database\Seeders;

use App\Models\Games\Shared\Games;
use App\Models\Games\Shared\Utils\GamePrizes;
use Illuminate\Database\Seeder;

class GamePrizesSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar los premios de los juegos.
     *
     * @return void
     */
    public function run(): void
    {
        // Obtener el ID del juego 'BINGO'
        $gameId = Games::where('name', 'BINGO')->value('id');

        if (!$gameId) {
            // Evitar error si no existe el juego
            $this->command->warn("Juego 'BINGO' no encontrado. Seeder GamePrizes omitido.");
            return;
        }

        $prizes = [
            ['description' => 'Cartón Lleno', 'code' => 'FLHE', 'game_id' => $gameId, 'is_active' => true],
            ['description' => 'Serial', 'code' => 'SRL', 'game_id' => $gameId, 'is_active' => true],
            ['description' => 'Canto', 'code' => 'SONG', 'game_id' => $gameId, 'is_active' => true],
        ];

        // Inserción masiva
        GamePrizes::insert($prizes);
    }
}
