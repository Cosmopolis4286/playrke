<?php

namespace App\Models\Games\Bingo\Creators;

use App\Models\Currency;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para las ventas de creadores de salas de bingo
 *
 * Representa las ventas realizadas por creadores de salas de bingo.
 * Incluye información sobre montos, comisiones y premios acumulados por sala y moneda.
 *
 * @package App\Models\Bingo\Creators
 * @author Sistema PlayRKE
 * @version 1.0
 *
 * @property string $id ID único de la venta
 * @property string $room ID de la sala de bingo
 * @property string $creator_user_id ID del usuario creador
 * @property string $currency ID de la moneda
 * @property float $sale_amount Monto total de ventas
 * @property float $rke_commn Comisión de RKE
 * @property float $room_commn Comisión de la sala
 * @property float $seller_commn Comisión del vendedor
 * @property float $prize_pool_amount Monto del premio acumulado
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read Currency $currency Moneda asociada
 * @property-read User $creatorUser Usuario creador asociado
 */
class BingoCreatorSale extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_creator_sales';

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'room',
        'creator_user_id',
        'currency',
        'sale_amount',
        'rke_commn',
        'room_commn',
        'seller_commn',
        'prize_pool_amount',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'sale_amount' => 'decimal:8',
        'rke_commn' => 'decimal:8',
        'room_commn' => 'decimal:8',
        'seller_commn' => 'decimal:8',
        'prize_pool_amount' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que se agregan automáticamente al modelo
     */
    protected $appends = ['total_commission', 'commission_percentage'];

    /**
     * Relación con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoCreatorSale>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    /**
     * Relación con la moneda
     *
     * @return BelongsTo<Currency, BingoCreatorSale>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    /**
     * Relación con el usuario creador
     *
     * @return BelongsTo<User, BingoCreatorSale>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Scope para obtener ventas por sala
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
     * Scope para obtener ventas por creador
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $creatorUserId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCreator($query, string $creatorUserId)
    {
        return $query->where('creator_user_id', $creatorUserId);
    }

    /**
     * Scope para obtener ventas por moneda
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $currency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope para obtener ventas por rango de montos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minAmount
     * @param float $maxAmount
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAmountRange($query, float $minAmount, float $maxAmount)
    {
        return $query->whereBetween('sale_amount', [$minAmount, $maxAmount]);
    }

    /**
     * Scope para obtener ventas por rango de fechas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope para obtener ventas con mayor volumen
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopByVolume($query, int $limit = 10)
    {
        return $query->orderByDesc('sale_amount')->limit($limit);
    }

    /**
     * Accessor para obtener el total de comisiones
     *
     * @return float
     */
    public function getTotalCommissionAttribute(): float
    {
        return $this->rke_commn + $this->room_commn + $this->seller_commn;
    }

    /**
     * Accessor para obtener el porcentaje de comisión
     *
     * @return float
     */
    public function getCommissionPercentageAttribute(): float
    {
        if ($this->sale_amount <= 0) {
            return 0.0;
        }

        return round(($this->total_commission / $this->sale_amount) * 100, 2);
    }

    /**
     * Accessor para verificar la integridad de los datos
     *
     * @return bool
     */
    public function getIsValidAttribute(): bool
    {
        $calculatedTotal = $this->rke_commn + $this->room_commn + $this->seller_commn + $this->prize_pool_amount;
        $tolerance = 0.01; // Tolerancia para errores de redondeo

        return abs($this->sale_amount - $calculatedTotal) <= $tolerance;
    }
}
