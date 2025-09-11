<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Games\Shared\Game;
use App\Models\Games\Shared\PrizePool\GamePrizePoolType;
use App\Models\Games\Shared\GameMode\GameMode;
use BradieTilley\Snowflake\Snowflake;

class GamePrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $snowflake = new Snowflake();
        $now = now();

        // Obtener el ID del juego 'BINGO'
        $gameId = Game::where('name', 'BINGO')->value('id');
        if (!$gameId) {
            $this->command->warn("Juego 'BINGO' no encontrado. Seeder omitido.");
            return;
        }

        // Traer todos los GameModes existentes
        $gameModes = GameMode::all();

        if ($gameModes->isEmpty()) {
            $this->command->warn("No hay GameModes disponibles. Seeder omitido.");
            return;
        }

        // Definir los prize_pool_type_ids que queremos crear
        $prizePoolTypeIds = [1, 2, 3];

        foreach ($gameModes as $mode) {
            foreach ($prizePoolTypeIds as $poolId) {
                GamePrizePoolType::updateOrCreate(
                    [
                        'game_id' => $gameId,
                        'game_mode_id' => $mode->id,
                        'prize_pool_type_id' => $poolId,
                    ],
                    [
                        'id' => $snowflake->id(),
                        'status' => 'ACTIVE',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
