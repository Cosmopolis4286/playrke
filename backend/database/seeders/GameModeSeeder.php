<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Games\Common\GameMode\GameMode;
use Illuminate\Database\Seeder;

/**
 * Modalidades de participación por juego (Solitario, Múltiple, Ranking,
 * etc.), administrables por admins vía GameModeController. Cada modo
 * pertenece a un único juego — no se comparten entre juegos.
 */
class GameModeSeeder extends Seeder
{
    public function run() : void
    {
        $modesByGame = [
            'BINGO' => [
                ['code' => 'SOLO', 'label' => 'Solitario'],
                ['code' => 'MULTI', 'label' => 'Múltiple'],
                ['code' => 'RANKING', 'label' => 'Ranking'],
            ],
            'TRIVIA' => [
                ['code' => 'FREE_FOR_ALL', 'label' => 'Libre'],
                ['code' => 'TEAMS', 'label' => 'Equipos'],
                ['code' => 'DUEL', 'label' => 'Duelo'],
                ['code' => 'TOURNAMENT', 'label' => 'Torneo'],
            ],
        ];

        foreach ($modesByGame as $gameName => $modes) {
            $gameId = Game::where('name', $gameName)->value('id');
            if (! $gameId) {
                $this->command->warn("Juego '{$gameName}' no encontrado. Modos omitidos.");

                continue;
            }

            foreach ($modes as $mode) {
                GameMode::updateOrCreate(
                    ['game_id' => $gameId, 'code' => $mode['code']],
                    ['label' => $mode['label'], 'status' => 'ACTIVE'],
                );
            }
        }
    }
}
