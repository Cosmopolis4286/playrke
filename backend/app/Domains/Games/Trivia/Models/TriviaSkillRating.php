<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Models;

use App\Models\BaseModel;

/**
 * Rating de habilidad de un jugador en Trivia.
 *
 * Un registro por usuario, se actualiza tras cada partida completada.
 * El tier se recalcula automáticamente al guardar.
 */
class TriviaSkillRating extends BaseModel
{
    // ── Tier thresholds ───────────────────────────────────────────────────────
    public const TIERS = [
        'diamond' => 2200,
        'platinum' => 1800,
        'gold' => 1500,
        'silver' => 1200,
        'bronze' => 0,
    ];

    protected $casts = [
        'rating' => 'integer',
        'peak_rating' => 'integer',
        'games_played' => 'integer',
        'wins' => 'integer',
        'win_rate' => 'float',
        'avg_correct_pct' => 'float',
        'avg_response_ms' => 'integer',
        'category_ratings' => 'array',
        'last_game_at' => 'datetime',
    ];
    protected $connection = 'pgsql_trivia';

    protected $fillable = [
        'user_id',
        'rating',
        'peak_rating',
        'games_played',
        'wins',
        'win_rate',
        'avg_correct_pct',
        'avg_response_ms',
        'tier',
        'category_ratings',
        'last_game_at',
    ];
    protected $table = 'trivia_skill_ratings';

    /**
     * Obtiene o crea el rating de un usuario.
     */
    public static function forUser(string $userId) : self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'rating' => 1000,
                'peak_rating' => 1000,
                'games_played' => 0,
                'wins' => 0,
                'win_rate' => 0,
                'avg_correct_pct' => 0,
                'avg_response_ms' => 0,
                'tier' => 'bronze',
            ],
        );
    }

    // ── Business logic ────────────────────────────────────────────────────────

    /**
     * Calcula el tier correcto basado en el rating actual.
     */
    public static function tierForRating(int $rating) : string
    {
        foreach (self::TIERS as $tier => $threshold) {
            if ($rating >= $threshold) {
                return $tier;
            }
        }

        return 'bronze';
    }

    public function getTierColorAttribute() : string
    {
        return match ($this->tier) {
            'diamond' => '#06b6d4',
            'platinum' => '#8b5cf6',
            'gold' => '#f59e0b',
            'silver' => '#94a3b8',
            default => '#cd7f32',
        };
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getTierLabelAttribute() : string
    {
        return match ($this->tier) {
            'diamond' => '💎 Diamante',
            'platinum' => '🔮 Platino',
            'gold' => '🥇 Oro',
            'silver' => '🥈 Plata',
            default => '🥉 Bronce',
        };
    }

    /**
     * Scope: top N jugadores por rating.
     */
    public function scopeLeaderboard($query, int $limit = 100)
    {
        return $query->orderByDesc('rating')->limit($limit);
    }

    /**
     * Scope: filtrar por tier.
     */
    public function scopeOfTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }
}
