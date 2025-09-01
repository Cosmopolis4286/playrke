<?php

namespace Database\Seeders;

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
        $entries = [
            ['game' => 1, 'game_mode' => 2, 'prize_pool_type' => 1, 'status' => 1],
            ['game' => 1, 'game_mode' => 2, 'prize_pool_type' => 2, 'status' => 1],
            ['game' => 1, 'game_mode' => 2, 'prize_pool_type' => 3, 'status' => 1],
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
