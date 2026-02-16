<?php

namespace App\Models\Games\Bingo\Draws;

use App\Models\Games\Bingo\Draws\BingoDrawCode;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los sorteos de bingo
 *
 * Representa los sorteos de bolillas/chips en las salas de bingo.
 * Incluye información sobre el chip sorteado, código, sala, ronda y estado.
 *
 * @package App\Models\Bingo\Draws
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property string $id ID único del sorteo
 * @property string $chip Número/letra del chip sorteado
 * @property string $code Código del sorteo
 * @property string $room ID de la sala de bingo
 * @property int $round Ronda del sorteo
 * @property int $time Tiempo del sorteo (timestamp)
 * @property int $status Estado del sorteo (1=pendiente, 2=activo, 3=ganador, 4=completado)
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read BingoDrawCode $codeDraw Código de sorteo asociado
 */
class BingoDraw extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_draws';

    /**
     * Estados disponibles para los sorteos
     */
    public const STATUSES = [
        1 => 'Pendiente',
        2 => 'Activo',
        3 => 'Ganador',
        4 => 'Completado',
    ];

    /**
     * Estados como constantes
     */
    public const STATUS_PENDING = 1;
    public const STATUS_ACTIVE = 2;
    public const STATUS_WINNING = 3;
    public const STATUS_COMPLETED = 4;

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'chip',
        'code',
        'room',
        'round',
        'time',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'round' => 'integer',
        'time' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que se agregan automáticamente al modelo
     */
    protected $appends = ['status_description', 'is_winning', 'formatted_time'];

    /**
     * Relación con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoDraw>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    /**
     * Relación con el código de sorteo
     *
     * @return BelongsTo<BingoDrawCode, BingoDraw>
     */
    public function codeDraw(): BelongsTo
    {
        return $this->belongsTo(BingoDrawCode::class, 'room', 'room');
    }

    /**
     * Scope para obtener sorteos por sala
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room', $roomId);
    }

    /**
     * Scope para obtener sorteos por estado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, int $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener sorteos por ronda
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $round
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRound($query, int $round)
    {
        return $query->where('round', $round);
    }

    /**
     * Scope para obtener sorteos pendientes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para obtener sorteos activos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para obtener sorteos ganadores
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWinning($query)
    {
        return $query->where('status', self::STATUS_WINNING);
    }

    /**
     * Scope para obtener sorteos completados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope para obtener sorteos por chip específico
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $chip
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByChip($query, string $chip)
    {
        return $query->where('chip', $chip);
    }

    /**
     * Scope para obtener sorteos por código
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope para obtener sorteos en orden de secuencia
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInSequence($query)
    {
        return $query->orderBy('time')->orderBy('created_at');
    }

    /**
     * Scope para obtener sorteos por rango de tiempo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $startTime
     * @param int $endTime
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTimeRange($query, int $startTime, int $endTime)
    {
        return $query->whereBetween('time', [$startTime, $endTime]);
    }

    /**
     * Accessor para obtener la descripción del estado
     *
     * @return string
     */
    public function getStatusDescriptionAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Desconocido';
    }

    /**
     * Accessor para verificar si es un sorteo ganador
     *
     * @return bool
     */
    public function getIsWinningAttribute(): bool
    {
        return $this->status === self::STATUS_WINNING;
    }

    /**
     * Accessor para obtener el tiempo formateado
     *
     * @return string
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->time ? date('H:i:s', $this->time) : '';
    }

    /**
     * Accessor para verificar si está activo
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Accessor para verificar si está completado
     *
     * @return bool
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Accessor para verificar si está pendiente
     *
     * @return bool
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
