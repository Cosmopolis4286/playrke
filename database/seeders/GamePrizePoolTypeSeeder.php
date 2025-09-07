<?php

namespace Database\Seeders;

use App\Models\Games\Shared\Games;
use App\Models\Games\Shared\PrizePool\GamePrizePoolType;
use Illuminate\Database\Seeder;

class GamePrizePoolTypeSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la tabla 'game_prize_pool_type' con los tipos de pool de premios.
     *
     * @return void
     */
    public function run(): void
    {
        // Obtener el ID del juego 'BINGO'
        $gameId = Games::where('name', 'BINGO')->value('id');

        if (!$gameId) {
            // Evitar error si no existe el juego
            $this->command->warn("Juego 'BINGO' no encontrado. Seeder GamePrizePool omitido.");
            return;
        }

        // Entradas a insertar o actualizar
        $entries = [
            ['game' => $gameId, 'game_mode' => 2, 'prize_pool_type' => 1, 'status' => 'ACTIVE'],
            ['game' => $gameId, 'game_mode' => 2, 'prize_pool_type' => 2, 'status' => 'ACTIVE'],
            ['game' => $gameId, 'game_mode' => 2, 'prize_pool_type' => 3, 'status' => 'ACTIVE'],
        ];

        // Inserta o actualiza los registros para evitar duplicados
        foreach ($entries as $entry) {
            GamePrizePoolType::updateOrCreate(
                [
                    'game' => $entry['game'],
                    'game_mode' => $entry['game_mode'],
                    'prize_pool_type' => $entry['prize_pool_type']
                ],
                ['status' => $entry['status']]
            );
        }
    }
}
