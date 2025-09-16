<?php

namespace App\Models\Games\Shared\Historical;

use App\Models\Games\Shared\Game;
use App\Models\Commerce\Currency;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modelo para la gestión del historial de juegos.
 *
 * Representa el registro histórico de todas las partidas jugadas,
 * incluyendo información del usuario, juego, ganancias y acciones realizadas.
 */
class PlayHistory extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'history_plays';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'played_at',    // Fecha y hora del juego
        'game_id',      // ID del juego
        'currency_id',  // ID de la moneda
        'user_id',      // ID del usuario
        'room_id',      // ID de la sala (opcional)
        'gain',         // Ganancia obtenida
        'action',       // Acción realizada
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'played_at' => 'datetime',
        'gain' => 'decimal:2',
        'room_id' => 'integer',
    ];

    /**
     * Relación con el usuario que jugó.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el juego jugado.
     *
     * @return BelongsTo
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Relación con la moneda utilizada.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Scope para filtrar por usuario.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por juego.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $gameId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGame($query, string $gameId)
    {
        return $query->where('game_id', $gameId);
    }

    /**
     * Scope para filtrar por sala.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Scope para filtrar por moneda.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currencyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCurrency($query, string $currencyId)
    {
        return $query->where('currency_id', $currencyId);
    }

    /**
     * Scope para obtener partidas con ganancias.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWinning($query)
    {
        return $query->where('gain', '>', 0);
    }

    /**
     * Scope para obtener partidas con pérdidas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLosing($query)
    {
        return $query->where('gain', '<', 0);
    }

    /**
     * Scope para filtrar por rango de fechas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('played_at', [$startDate, $endDate]);
    }

    /**
     * Scope para obtener actividad reciente.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours Número de horas hacia atrás
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('played_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope para buscar por acción.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', 'LIKE', "%{$action}%");
    }

    /**
     * Verificar si fue una partida ganadora.
     *
     * @return bool
     */
    public function isWinning(): bool
    {
        return $this->gain > 0;
    }

    /**
     * Verificar si fue una partida perdedora.
     *
     * @return bool
     */
    public function isLosing(): bool
    {
        return $this->gain < 0;
    }

    /**
     * Verificar si fue una partida sin ganancias ni pérdidas.
     *
     * @return bool
     */
    public function isNeutral(): bool
    {
        return $this->gain == 0;
    }

    /**
     * Obtener la ganancia formateada.
     *
     * @return string
     */
    public function getFormattedGain(): string
    {
        $symbol = $this->currency->symbol ?? '$';
        $gain = number_format($this->gain, 2);

        if ($this->isWinning()) {
            return "+{$symbol}{$gain}";
        } elseif ($this->isLosing()) {
            return "-{$symbol}" . abs($gain);
        } else {
            return "{$symbol}{$gain}";
        }
    }

    /**
     * Obtener información completa de la partida.
     *
     * @return string
     */
    public function getPlaySummary(): string
    {
        $gameName = $this->game->name ?? 'Juego desconocido';
        $userName = $this->user->name ?? 'Usuario desconocido';
        $gain = $this->getFormattedGain();
        $date = $this->played_at->format('d/m/Y H:i');

        return sprintf(
            '%s jugó %s el %s - %s (%s)',
            $userName,
            $gameName,
            $date,
            $gain,
            $this->action
        );
    }

    /**
     * Obtener el tiempo transcurrido desde la partida.
     *
     * @return string
     */
    public function getTimeAgo(): string
    {
        return $this->played_at->diffForHumans();
    }

    /**
     * Verificar si la partida pertenece a un usuario específico.
     *
     * @param string $userId ID del usuario
     * @return bool
     */
    public function belongsToUser(string $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Verificar si la partida fue en una sala específica.
     *
     * @param int $roomId ID de la sala
     * @return bool
     */
    public function isInRoom(int $roomId): bool
    {
        return $this->room_id === $roomId;
    }

    /**
     * Verificar si la partida fue hoy.
     *
     * @return bool
     */
    public function isToday(): bool
    {
        return $this->played_at->isToday();
    }

    /**
     * Verificar si la partida fue esta semana.
     *
     * @return bool
     */
    public function isThisWeek(): bool
    {
        return $this->played_at->isCurrentWeek();
    }

    /**
     * Verificar si la partida fue este mes.
     *
     * @return bool
     */
    public function isThisMonth(): bool
    {
        return $this->played_at->isCurrentMonth();
    }

    /**
     * Obtener la hora del día en que se jugó.
     *
     * @return int Hora (0-23)
     */
    public function getHourOfDay(): int
    {
        return $this->played_at->hour;
    }

    /**
     * Obtener el día de la semana en que se jugó.
     *
     * @return int Día de la semana (0=Domingo, 6=Sábado)
     */
    public function getDayOfWeek(): int
    {
        return $this->played_at->dayOfWeek;
    }

    /**
     * Accessor para obtener el nombre del día de la semana.
     *
     * @return string
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado'
        ];

        return $days[$this->getDayOfWeek()] ?? 'Desconocido';
    }

    /**
     * Accessor para determinar el tipo de resultado.
     *
     * @return string
     */
    public function getResultTypeAttribute(): string
    {
        if ($this->isWinning()) {
            return 'ganancia';
        } elseif ($this->isLosing()) {
            return 'pérdida';
        } else {
            return 'neutral';
        }
    }

    /**
     * Scope para ordenar por fecha de juego.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByPlayDate($query, string $direction = 'desc')
    {
        return $query->orderBy('played_at', $direction);
    }

    /**
     * Scope para ordenar por ganancia.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByGain($query, string $direction = 'desc')
    {
        return $query->orderBy('gain', $direction);
    }
}
