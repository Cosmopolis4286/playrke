<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            // Logros de Juegos
            [
                'code' => 'first_game',
                'name' => 'Primera Partida',
                'description' => 'Juega tu primera partida de bingo',
                'icon' => 'bingo.svg',
                'badge_color' => 'blue',
                'xp_reward' => 50,
                'requirements' => ['type' => 'first_game', 'target' => 1],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'first_win',
                'name' => 'Primera Victoria',
                'description' => 'Gana tu primera partida de bingo',
                'icon' => 'trophy.svg',
                'badge_color' => 'gold',
                'xp_reward' => 100,
                'requirements' => ['type' => 'first_win', 'target' => 1],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'games_played_10',
                'name' => 'Jugador Iniciado',
                'description' => 'Juega 10 partidas de bingo',
                'icon' => 'gamepad.svg',
                'badge_color' => 'green',
                'xp_reward' => 150,
                'requirements' => ['type' => 'games_played', 'target' => 10],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'games_played_50',
                'name' => 'Jugador Experimentado',
                'description' => 'Juega 50 partidas de bingo',
                'icon' => 'star.svg',
                'badge_color' => 'purple',
                'xp_reward' => 300,
                'requirements' => ['type' => 'games_played', 'target' => 50],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'games_played_100',
                'name' => 'Jugador Veterano',
                'description' => 'Juega 100 partidas de bingo',
                'icon' => 'crown.svg',
                'badge_color' => 'orange',
                'xp_reward' => 500,
                'requirements' => ['type' => 'games_played', 'target' => 100],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'wins_10',
                'name' => 'Ganador Consistente',
                'description' => 'Gana 10 partidas de bingo',
                'icon' => 'medal.svg',
                'badge_color' => 'yellow',
                'xp_reward' => 250,
                'requirements' => ['type' => 'games_won', 'target' => 10],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'wins_50',
                'name' => 'Maestro del Bingo',
                'description' => 'Gana 50 partidas de bingo',
                'icon' => 'master.svg',
                'badge_color' => 'red',
                'xp_reward' => 750,
                'requirements' => ['type' => 'games_won', 'target' => 50],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 7,
            ],

            // Logros Sociales
            [
                'code' => 'first_referral',
                'name' => 'Amigo Invitado',
                'description' => 'Invita a tu primer amigo al juego',
                'icon' => 'users.svg',
                'badge_color' => 'pink',
                'xp_reward' => 200,
                'requirements' => ['type' => 'referrals', 'target' => 1],
                'category' => 'social',
                'is_hidden' => false,
                'sort_order' => 8,
            ],
            [
                'code' => 'referrals_5',
                'name' => 'Influencer',
                'description' => 'Invita a 5 amigos al juego',
                'icon' => 'share.svg',
                'badge_color' => 'indigo',
                'xp_reward' => 500,
                'requirements' => ['type' => 'referrals', 'target' => 5],
                'category' => 'social',
                'is_hidden' => false,
                'sort_order' => 9,
            ],

            // Logros de Niveles
            [
                'code' => 'level_5',
                'name' => 'Nivel 5',
                'description' => 'Alcanza el nivel 5',
                'icon' => 'level-up.svg',
                'badge_color' => 'teal',
                'xp_reward' => 100,
                'requirements' => ['type' => 'level_reached', 'target' => 5],
                'category' => 'milestones',
                'is_hidden' => false,
                'sort_order' => 10,
            ],
            [
                'code' => 'level_10',
                'name' => 'Nivel 10',
                'description' => 'Alcanza el nivel 10',
                'icon' => 'shield.svg',
                'badge_color' => 'cyan',
                'xp_reward' => 250,
                'requirements' => ['type' => 'level_reached', 'target' => 10],
                'category' => 'milestones',
                'is_hidden' => false,
                'sort_order' => 11,
            ],
            [
                'code' => 'level_25',
                'name' => 'Nivel 25',
                'description' => 'Alcanza el nivel 25',
                'icon' => 'diamond.svg',
                'badge_color' => 'purple',
                'xp_reward' => 500,
                'requirements' => ['type' => 'level_reached', 'target' => 25],
                'category' => 'milestones',
                'is_hidden' => false,
                'sort_order' => 12,
            ],

            // Logros de Racha
            [
                'code' => 'streak_7',
                'name' => 'Semana Activa',
                'description' => 'Mantén una racha de 7 días',
                'icon' => 'calendar.svg',
                'badge_color' => 'green',
                'xp_reward' => 200,
                'requirements' => ['type' => 'login_streak', 'target' => 7],
                'category' => 'milestones',
                'is_hidden' => false,
                'sort_order' => 13,
            ],
            [
                'code' => 'streak_30',
                'name' => 'Mes Activo',
                'description' => 'Mantén una racha de 30 días',
                'icon' => 'fire.svg',
                'badge_color' => 'orange',
                'xp_reward' => 1000,
                'requirements' => ['type' => 'login_streak', 'target' => 30],
                'category' => 'milestones',
                'is_hidden' => false,
                'sort_order' => 14,
            ],

            // Logros Especiales (Ocultos) — condición 'custom' implementada en
            // AchievementService::checkCustomRequirement().
            [
                'code' => 'lucky_winner',
                'name' => 'Ganador Suerte',
                'description' => 'Gana justo cuando el sorteo estaba por agotarse',
                'icon' => 'clover.svg',
                'badge_color' => 'green',
                'xp_reward' => 300,
                'requirements' => ['type' => 'custom', 'target' => 1],
                'category' => 'special',
                'is_active' => true,
                'is_hidden' => true,
                'sort_order' => 15,
            ],
            [
                'code' => 'early_bird',
                'name' => 'Madrugador',
                'description' => 'Juega una partida antes de las 6 AM',
                'icon' => 'sun.svg',
                'badge_color' => 'yellow',
                'xp_reward' => 150,
                'requirements' => ['type' => 'custom', 'target' => 1],
                'category' => 'special',
                'is_active' => true,
                'is_hidden' => true,
                'sort_order' => 16,
            ],
            [
                'code' => 'night_owl',
                'name' => 'Noctámbulo',
                'description' => 'Juega una partida después de medianoche',
                'icon' => 'moon.svg',
                'badge_color' => 'indigo',
                'xp_reward' => 150,
                'requirements' => ['type' => 'custom', 'target' => 1],
                'category' => 'special',
                'is_active' => true,
                'is_hidden' => true,
                'sort_order' => 17,
            ],

            // Logros de Ganancias
            [
                'code' => 'winnings_1000',
                'name' => 'Premiador',
                'description' => 'Acumula $1000 en premios',
                'icon' => 'money.svg',
                'badge_color' => 'green',
                'xp_reward' => 200,
                'requirements' => ['type' => 'total_winnings', 'target' => 1000],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 18,
            ],
            [
                'code' => 'winnings_10000',
                'name' => 'Gran Premiador',
                'description' => 'Acumula $10000 en premios',
                'icon' => 'cash.svg',
                'badge_color' => 'gold',
                'xp_reward' => 1000,
                'requirements' => ['type' => 'total_winnings', 'target' => 10000],
                'category' => 'games',
                'is_hidden' => false,
                'sort_order' => 19,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::create($achievement);
        }
    }
}
