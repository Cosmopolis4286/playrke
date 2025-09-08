<?php

namespace Database\Seeders;

use App\Models\Games\Shared\Games;
use App\Models\Games\Shared\PrizePool\GamePrizePoolType;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class GamePrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el ID del juego 'BINGO'
        $gameId = Games::where('name', 'BINGO')->value('id');

        if (!$gameId) {
            $this->command->warn("Juego 'BINGO' no encontrado. Seeder omitido.");
            return;
        }

        $entries = [
            ['game_mode_id' => 2, 'prize_pool_type_id' => 1],
            ['game_mode_id' => 2, 'prize_pool_type_id' => 2],
            ['game_mode_id' => 2, 'prize_pool_type_id' => 3],
        ];

        // Crear instancia de Snowflake
        $snowflake = new Snowflake();

        foreach ($entries as $entry) {
            GamePrizePoolType::updateOrCreate(
                [
                    'game_id' => $gameId,
                    'game_mode_id' => $entry['game_mode_id'],
                    'prize_pool_type_id' => $entry['prize_pool_type_id'],
                ],
                [
                    'id' => $snowflake->id(), // ✅ ID Snowflake único
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}
