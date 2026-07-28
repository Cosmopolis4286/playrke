<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use Illuminate\Database\Seeder;

class PrizePoolTypeSeeder extends Seeder
{
    public function run(): void
    {
        $typesByGame = [
            'BINGO' => [
                ['code' => 'ACCUMULATIVE', 'label' => 'Bote Acumulativo'],
                ['code' => 'FIXED',        'label' => 'Bote Fijo'],
                ['code' => 'MIXED',        'label' => 'Bote Mixto'],
                ['code' => 'PRIZE',        'label' => 'Premio/Servicio'],
            ],
        ];

        foreach ($typesByGame as $gameName => $types) {
            $gameId = Game::where('name', $gameName)->value('id');
            if (!$gameId) {
                $this->command->warn("Juego '{$gameName}' no encontrado. Tipos de bote omitidos.");
                continue;
            }

            foreach ($types as $type) {
                PrizePoolType::updateOrCreate(
                    ['game_id' => $gameId, 'code' => $type['code']],
                    ['label' => $type['label'], 'status' => 'ACTIVE']
                );
            }
        }
    }
}
