<?php

namespace Database\Seeders;

use App\Models\DailyMission;
use Illuminate\Database\Seeder;

/**
 * Catálogo mínimo de misiones diarias — ver
 * progreso/estrategia-producto-plan.md, Fase 6. updateOrCreate() (no
 * create() plano como AchievementSeeder) para que correr el seeder de nuevo
 * no duplique filas si ya existen por 'code'.
 */
class DailyMissionSeeder extends Seeder
{
    public function run(): void
    {
        $missions = [
            [
                'code' => 'play_3_games',
                'name' => 'Juega 3 partidas hoy',
                'description' => 'Participa en 3 partidas de cualquier juego (Bingo o Trivia) hoy',
                'requirement_type' => DailyMission::REQUIREMENT_GAMES_PLAYED,
                'target' => 3,
                'xp_reward' => 75,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'win_1_game',
                'name' => 'Gana una partida hoy',
                'description' => 'Gana al menos 1 partida hoy',
                'requirement_type' => DailyMission::REQUIREMENT_GAMES_WON,
                'target' => 1,
                'xp_reward' => 150,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($missions as $mission) {
            DailyMission::updateOrCreate(['code' => $mission['code']], $mission);
        }
    }
}
