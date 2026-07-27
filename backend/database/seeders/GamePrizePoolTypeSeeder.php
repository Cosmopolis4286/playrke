<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\Games\Common\PrizePool\GamePrizePoolType;
use App\Models\Games\Common\ConnectionType\ConnectionType;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use BradieTilley\Snowflake\Snowflake;

class GamePrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $snowflake = new Snowflake();
        $now = now();

        // Buscar el ID del juego "BINGO"
        $gameId = Game::where('name', 'BINGO')->value('id');
        if (!$gameId) {
            $this->command->warn("Juego 'BINGO' no encontrado. Seeder omitido.");
            return;
        }

        // Obtener todos los tipos de conexión
        $connectionTypes = ConnectionType::all();
        if ($connectionTypes->isEmpty()) {
            $this->command->warn("No hay ConnectionTypes disponibles. Seeder omitido.");
            return;
        }

        // Obtener todos los tipos de prize pool
        $prizePoolTypes = PrizePoolType::all();
        if ($prizePoolTypes->isEmpty()) {
            $this->command->warn("No hay PrizePoolTypes disponibles. Seeder omitido.");
            return;
        }

        // Generar todas las combinaciones
        foreach ($connectionTypes as $connectionType) {
            foreach ($prizePoolTypes as $poolType) {
                GamePrizePoolType::updateOrCreate(
                    [
                        'game_id' => $gameId,
                        'connection_type_id' => $connectionType->id,
                        'prize_pool_type_id' => $poolType->id,
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
