<?php

namespace App\Models\Roles\Player\External;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vincula un ExternalPlayer (comprador sin cuenta) con cualquier entidad
 * transaccional de cualquier juego — un cartón de Bingo hoy
 * (App\Domains\Games\Bingo\Models\Card\BingoCard::class), una entrada/pago
 * de Trivia mañana, etc.
 *
 * Polimórfico "manual" (participable_type + participable_id como JSON), NO
 * morphTo() nativo de Eloquent — mismo patrón que App\Models\Audit, elegido
 * por consistencia con esa convención ya establecida en el proyecto.
 */
class ExternalPlayerParticipation extends BaseModel
{
    protected $casts = [
        'external_player_id' => 'string',
        'participable_id' => 'array',
    ];
    protected $fillable = [
        'external_player_id',
        'participable_type',
        'participable_id',
    ];

    /**
     * IDs de las entidades participables de un tipo dado para un jugador
     * externo — ej. IDs de BingoCard para filtrar "mis cartones".
     *
     * @return array<int, string>
     */
    public static function participableIdsFor(int|string $externalPlayerId, string $participableType) : array
    {
        return static::forExternalPlayer($externalPlayerId)
            ->ofParticipable($participableType)
            ->get()
            ->map(fn (self $p) => (string) $p->participable_id)
            ->all();
    }

    /**
     * Crea la participación para una entidad de juego dada (ej. un BingoCard
     * recién generado) — helper simétrico a Auditable::serializeValue()
     * (mismo criterio: el ID se guarda como escalar dentro del JSON).
     */
    public static function recordFor(int|string $externalPlayerId, string $participableType, int|string $participableId) : self
    {
        return static::create([
            'external_player_id' => $externalPlayerId,
            'participable_type' => $participableType,
            'participable_id' => $participableId,
        ]);
    }

    public function externalPlayer() : BelongsTo
    {
        return $this->belongsTo(ExternalPlayer::class, 'external_player_id');
    }

    public function scopeForExternalPlayer(Builder $query, int|string $externalPlayerId) : Builder
    {
        return $query->where('external_player_id', $externalPlayerId);
    }

    public function scopeOfParticipable(Builder $query, string $modelClass) : Builder
    {
        return $query->where('participable_type', $modelClass);
    }
}
