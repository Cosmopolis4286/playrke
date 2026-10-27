<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Models\TriviaSkillRating;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza el skill rating de cada jugador al terminar una partida.
 *
 * Algoritmo — ELO posicional simplificado:
 * ─────────────────────────────────────────
 * El ELO clásico es 1v1. Aquí adaptamos para N jugadores con ranking posicional:
 *
 *   delta = K × (actualScore - expectedScore)
 *
 * donde:
 *   K             = factor de volatilidad (mayor K = cambios más grandes)
 *                   K=40 para nuevos (<20 juegos), K=20 para establecidos
 *   actualScore   = puntuación real del jugador en la partida (0.0–1.0)
 *                   basada en posición: 1° → 1.0, último → 0.0
 *   expectedScore = probabilidad esperada de ganar basada en el rating actual
 *                   comparado con el promedio de oponentes
 *
 * Adicionalmente aplicamos un bonus de habilidad (+10 si >70% correct) y
 * un cap de movimiento máximo por partida (±100 pts) para evitar swings extremos.
 *
 * El tier se recalcula y actualiza en cada operación.
 */
final class SkillRatingService
{
    private const K_ESTABLISHED = 20;   // ≥ 20 partidas
    private const K_NEW = 40;   // < 20 partidas
    private const MAX_DELTA = 100;  // cambio máximo por partida
    private const SKILL_BONUS = 10;   // bonus si >70% respuestas correctas
    private const SKILL_BONUS_THRESHOLD = 0.70;

    /**
     * Rating de un jugador específico.
     */
    public function getRating(string $userId) : array
    {
        $r = TriviaSkillRating::forUser($userId);

        return [
            'userId' => $r->user_id,
            'rating' => $r->rating,
            'peakRating' => $r->peak_rating,
            'tier' => $r->tier,
            'tierLabel' => $r->tier_label,
            'tierColor' => $r->tier_color,
            'gamesPlayed' => $r->games_played,
            'wins' => $r->wins,
            'winRate' => $r->win_rate,
            'avgCorrectPct' => $r->avg_correct_pct,
            'avgResponseMs' => $r->avg_response_ms,
            'lastGameAt' => $r->last_game_at?->toISOString(),
        ];
    }

    /**
     * Leaderboard global de skill ratings.
     *
     * @return array [{userId, rating, tier, wins, gamesPlayed, winRate}]
     */
    public function globalLeaderboard(int $limit = 100, ?string $tier = null) : array
    {
        $query = TriviaSkillRating::leaderboard($limit);

        if ($tier) {
            $query->ofTier($tier);
        }

        return $query->get()->map(fn ($r) => [
            'userId' => $r->user_id,
            'rating' => $r->rating,
            'peakRating' => $r->peak_rating,
            'tier' => $r->tier,
            'tierLabel' => $r->tier_label,
            'tierColor' => $r->tier_color,
            'gamesPlayed' => $r->games_played,
            'wins' => $r->wins,
            'winRate' => $r->win_rate,
            'avgCorrectPct' => $r->avg_correct_pct,
            'lastGameAt' => $r->last_game_at?->toISOString(),
        ])->all();
    }

    /**
     * Actualiza los ratings de todos los jugadores de una partida.
     *
     * @param array $finalRanks [{playerId, position, correctAnswers, totalQuestions, avgTimeMs}]
     */
    public function updateAfterGame(array $finalRanks) : void
    {
        if (count($finalRanks) < 2) {
            return; // No tiene sentido actualizar con 1 jugador
        }

        // Cargar/crear todos los ratings de golpe
        $playerIds = array_column($finalRanks, 'playerId');
        $ratings = [];
        foreach ($playerIds as $id) {
            $ratings[$id] = TriviaSkillRating::forUser($id);
        }

        // Rating promedio de oponentes (para expected score)
        $avgRating = array_sum(array_map(fn ($r) => $r->rating, $ratings)) / count($ratings);
        $n = count($finalRanks);

        DB::connection('pgsql_trivia')->transaction(function() use ($finalRanks, $ratings, $avgRating, $n) {
            foreach ($finalRanks as $rank) {
                $userId = $rank['playerId'];
                $position = (int) $rank['position'];
                $correct = (int) ($rank['correctAnswers'] ?? 0);
                $total = (int) ($rank['totalQuestions'] ?? max(1, $correct));
                $avgTime = (float) ($rank['avgTimeMs'] ?? 0);

                $skillRating = $ratings[$userId];

                // K factor según experiencia
                $K = $skillRating->games_played < 20 ? self::K_NEW : self::K_ESTABLISHED;

                // Actual score: posición normalizada (1° = 1.0, último = 0.0)
                $actualScore = ($n - $position) / max(1, $n - 1);

                // Expected score ELO vs promedio
                $expectedScore = 1 / (1 + 10 ** (($avgRating - $skillRating->rating) / 400));

                // Delta base
                $delta = (int) round($K * ($actualScore - $expectedScore));

                // Bonus de habilidad (respuestas correctas)
                $correctPct = $total > 0 ? $correct / $total : 0;
                if ($correctPct >= self::SKILL_BONUS_THRESHOLD) {
                    $delta += self::SKILL_BONUS;
                }

                // Cap
                $delta = max(-self::MAX_DELTA, min(self::MAX_DELTA, $delta));

                $newRating = max(100, $skillRating->rating + $delta);
                $peakRating = max($skillRating->peak_rating, $newRating);

                // Estadísticas rolling
                $gamesPlayed = $skillRating->games_played + 1;
                $isWin = $position <= 3 ? 1 : 0;
                $wins = $skillRating->wins + $isWin;
                $winRate = round($wins / $gamesPlayed, 4);
                $avgCorrectPct = round(
                    ($skillRating->avg_correct_pct * $skillRating->games_played + $correctPct) / $gamesPlayed,
                    4,
                );
                $avgResponseMs = $gamesPlayed > 1
                    ? (int) round(($skillRating->avg_response_ms * $skillRating->games_played + $avgTime) / $gamesPlayed)
                    : (int) $avgTime;

                $tier = TriviaSkillRating::tierForRating($newRating);

                $skillRating->update([
                    'rating' => $newRating,
                    'peak_rating' => $peakRating,
                    'games_played' => $gamesPlayed,
                    'wins' => $wins,
                    'win_rate' => $winRate,
                    'avg_correct_pct' => $avgCorrectPct,
                    'avg_response_ms' => $avgResponseMs,
                    'tier' => $tier,
                    'last_game_at' => now(),
                ]);
            }
        });
    }
}
