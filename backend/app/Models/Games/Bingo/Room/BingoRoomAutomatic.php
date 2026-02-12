<?php

namespace App\Models\Games\Bingo\Room;

use App\Models\Games\Common\GameMode\GameModes;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para las salas de bingo automáticas
 *
 * Representa las salas de bingo programadas automáticamente.
 * Incluye configuración de horarios, precios, comisiones y patrones de juego.
 *
 * @package App\Models\Games\Bingo\Room
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property string $id ID único de la sala automática
 * @property string $description Descripción de la sala
 * @property string $date Fecha programada
 * @property array $days Días de la semana programados
 * @property string $time Hora programada
 * @property string $game_mode Modo de juego
 * @property bool $in_live Si está en vivo
 * @property string $prize_pool_type Tipo de premio
 * @property float $amount_added_prize_pool Monto agregado al premio
 * @property string $room_type Tipo de sala
 * @property float $rke_commn Comisión RKE
 * @property float $room_commn Comisión de sala
 * @property float $card_price Precio de carta
 * @property array $allowed_currencies Monedas permitidas
 * @property int $time_chip Tiempo por chip
 * @property int $rounds Número de rondas
 * @property float $rounds_pct Porcentaje de rondas
 * @property float $serials_pct Porcentaje de seriales
 * @property int $num_serials Número de seriales
 * @property float $jackpot_pct Porcentaje de jackpot
 * @property float $plays_pct Porcentaje de jugadas
 * @property array $bingo_patterns Patrones de bingo
 * @property string $creator_type Tipo de creador
 * @property string $creator_id ID del creador
 * @property string $creator_user_id ID del usuario creador
 * @property string $status Estado de la sala
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read GameModes $gameMode Modo de juego asociado
 * @property-read PrizePoolType $prizePoolType Tipo de premio asociado
 * @property-read User $creatorUser Usuario creador asociado
 */
class BingoRoomAutomatic extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_rooms_automatic';

    /**
     * Estados disponibles para las salas
     */
    public const STATUSES = [
        'active' => 'Activa',
        'inactive' => 'Inactiva',
        'scheduled' => 'Programada',
        'running' => 'En Ejecución',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ];

    /**
     * Estados como constantes
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Días de la semana
     */
    public const DAYS = [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'description',
        'date',
        'days',
        'time',
        'game_mode',
        'in_live',
        'prize_pool_type',
        'amount_added_prize_pool',
        'room_type',
        'rke_commn',
        'room_commn',
        'card_price',
        'allowed_currencies',
        'time_chip',
        'rounds',
        'rounds_pct',
        'serials_pct',
        'num_serials',
        'jackpot_pct',
        'plays_pct',
        'bingo_patterns',
        'creator_type',
        'creator_id',
        'creator_user_id',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'allowed_currencies' => 'array',
        'bingo_patterns' => 'array',
        'days' => 'array',
        'in_live' => 'string',
        'date' => 'date',
        'time' => 'datetime:H:i:s',
        'card_price' => 'decimal:8',
        'rke_commn' => 'decimal:8',
        'room_commn' => 'decimal:8',
        'amount_added_prize_pool' => 'decimal:8',
        'rounds_pct' => 'decimal:8',
        'serials_pct' => 'decimal:8',
        'jackpot_pct' => 'decimal:8',
        'plays_pct' => 'decimal:8',
        'time_chip' => 'integer',
        'rounds' => 'integer',
        'num_serials' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que se agregan automáticamente al modelo
     */
    protected $appends = [
        'status_description',
        'formatted_time',
        'total_commission',
        'days_description'
    ];

    /**
     * Relación con el modo de juego
     *
     * @return BelongsTo<GameModes, BingoRoomAutomatic>
     */
    public function gameMode(): BelongsTo
    {
        return $this->belongsTo(GameModes::class, 'game_mode');
    }

    /**
     * Relación con el tipo de premio
     *
     * @return BelongsTo<PrizePoolType, BingoRoomAutomatic>
     */
    public function prizePoolType(): BelongsTo
    {
        return $this->belongsTo(PrizePoolType::class, 'prize_pool_type');
    }

    /**
     * Relación con el usuario creador
     *
     * @return BelongsTo<User, BingoRoomAutomatic>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Scope para obtener salas por estado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener salas activas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para obtener salas programadas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope para obtener salas en ejecución
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope para obtener salas en vivo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLive($query)
    {
        return $query->where('in_live', 'Y');
    }

    /**
     * Scope para obtener salas por creador
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $creatorType
     * @param string $creatorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCreator($query, string $creatorType, string $creatorId)
    {
        return $query->where('creator_type', $creatorType)
            ->where('creator_id', $creatorId);
    }

    /**
     * Scope para obtener salas por fecha
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope para obtener salas de hoy
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    /**
     * Scope para obtener salas por rango de precios
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minPrice
     * @param float $maxPrice
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriceRange($query, float $minPrice, float $maxPrice)
    {
        return $query->whereBetween('card_price', [$minPrice, $maxPrice]);
    }

    /**
     * Accessor para obtener la descripción del estado
     *
     * @return string
     */
    public function getStatusDescriptionAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Accessor para obtener el tiempo formateado
     *
     * @return string
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->time ? $this->time->format('H:i') : '';
    }

    /**
     * Accessor para obtener el total de comisiones
     *
     * @return float
     */
    public function getTotalCommissionAttribute(): float
    {
        return $this->rke_commn + $this->room_commn;
    }

    /**
     * Accessor para obtener la descripción de los días
     *
     * @return string
     */
    public function getDaysDescriptionAttribute(): string
    {
        if (!$this->days || !is_array($this->days)) {
            return '';
        }

        $descriptions = [];
        foreach ($this->days as $day) {
            $descriptions[] = self::DAYS[$day] ?? $day;
        }

        return implode(', ', $descriptions);
    }

    /**
     * Accessor para verificar si está activa
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Accessor para verificar si está programada
     *
     * @return bool
     */
    public function getIsScheduledAttribute(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Accessor para verificar si está en ejecución
     *
     * @return bool
     */
    public function getIsRunningAttribute(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Accessor para verificar si está completada
     *
     * @return bool
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Verificar si la sala está configurada correctamente
     *
     * @return bool
     */
    public function isValidConfiguration(): bool
    {
        return !empty($this->description) &&
               !empty($this->date) &&
               !empty($this->time) &&
               $this->card_price > 0 &&
               $this->rounds > 0;
    }

    /**
     * Obtener el próximo horario de ejecución
     *
     * @return \Carbon\Carbon|null
     */
    public function getNextExecutionTime(): ?\Carbon\Carbon
    {
        if (!$this->date || !$this->time) {
            return null;
        }

        return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->date->format('Y-m-d') . ' ' . $this->time->format('H:i:s'));
    }

    /**
     * Verificar si puede ejecutarse ahora
     *
     * @return bool
     */
    public function canExecuteNow(): bool
    {
        $nextExecution = $this->getNextExecutionTime();

        return $this->status &&
               $nextExecution &&
               $nextExecution->isPast();
    }
}
