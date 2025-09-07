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
            ['game_id' => $gameId, 'game_mode_id' => 2, 'prize_pool_type_id' => 1, 'status' => 'ACTIVE'],
            ['game_id' => $gameId, 'game_mode_id' => 2, 'prize_pool_type_id' => 2, 'status' => 'ACTIVE'],
            ['game_id' => $gameId, 'game_mode_id' => 2, 'prize_pool_type_id' => 3, 'status' => 'ACTIVE'],
        ];

        // Inserta o actualiza los registros para evitar duplicados
        foreach ($entries as $entry) {
            GamePrizePoolType::updateOrCreate(
                [
                    'game_id' => $entry['game_id'],
                    'game_mode_id' => $entry['game_mode_id'],
                    'prize_pool_type_id' => $entry['prize_pool_type_id']
                ],
                ['status' => $entry['status']]
            );
        }
    }
}
