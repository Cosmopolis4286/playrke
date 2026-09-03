<?php

namespace App\Domains\Platform\WinEvent\Models;

use App\Models\Currency;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evento de ganancia de la plataforma — fuente de verdad única para el feed
 * en vivo de ganadores, sin importar el juego o si el premio es dinero, un
 * objeto o un servicio.
 */
class WinEvent extends Model
{
    use SnowflakeId;

    protected $table = 'platform_win_events';

    public const SOURCE_GAME_ENGINE  = 'game_engine';
    public const SOURCE_ADMIN_MANUAL = 'admin_manual';
    public const SOURCE_EXTERNAL_API = 'external_api';

    public const PRIZE_MONEY   = 'money';
    public const PRIZE_ITEM    = 'item';
    public const PRIZE_SERVICE = 'service';

    protected $fillable = [
        'user_id',
        'winner_name_override',
        'game_slug',
        'game_name',
        'source_type',
        'reported_by_user_id',
        'prize_kind',
        'prize_label',
        'currency_id',
        'amount_usd',
        'amount_local',
        'source_reference',
        'metadata',
    ];

    protected $casts = [
        'amount_usd'   => 'decimal:2',
        'amount_local' => 'decimal:4',
        'metadata'     => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function isMoney(): bool
    {
        return $this->prize_kind === self::PRIZE_MONEY;
    }

    public function scopeRecent($query, int $minutes = 5)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Nombre a mostrar: usuario real de la plataforma si existe, si no el
     * nombre libre indicado al registrar el evento (premios externos).
     *
     * Sin enmascarar — solo para consumo interno (panel admin, logs de
     * actividad). Cualquier salida pública o por broadcast debe usar
     * maskedWinnerName() en su lugar (ver WinEventService::broadcast(),
     * WinEventController y PublicController::root()).
     */
    public function resolveWinnerName(): string
    {
        return $this->user?->full_name
            ?: ($this->user?->username
            ?: ($this->winner_name_override ?: 'Jugador'));
    }

    /**
     * Nombre enmascarado para cualquier salida pública o en tiempo real
     * (WebSocket, endpoints REST públicos): mismo criterio de privacidad
     * que el resto de la plataforma, aplicado en el origen del dato para
     * que ningún consumidor (autenticado o no) reciba el nombre completo.
     * Regla idéntica a usePlatformWinEvents.ts::maskName() en el frontend.
     */
    public function maskedWinnerName(): string
    {
        $name = $this->resolveWinnerName();

        if ($name === '') {
            return '***';
        }

        if (mb_strlen($name) <= 3) {
            return mb_substr($name, 0, 1) . '***';
        }

        $visible = mb_substr($name, 0, 3);
        $maskLength = min(mb_strlen($name) - 3, 4);

        return $visible . str_repeat('*', $maskLength);
    }
}
