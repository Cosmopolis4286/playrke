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
            ['description' => 'Cartón Lleno', 'code' => 'FLHE', 'game' => $gameId, 'status' => 1],
            ['description' => 'Serial', 'code' => 'SRL', 'game' => $gameId, 'status' => 1],
            ['description' => 'Canto', 'code' => 'SONG', 'game' => $gameId, 'status' => 1],
        ];

        // Inserción masiva
        GamePrizes::insert($prizes);
    }
}
