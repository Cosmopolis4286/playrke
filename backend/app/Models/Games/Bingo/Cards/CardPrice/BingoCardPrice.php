<?php

declare(strict_types=1);

namespace App\Models\Bingo\Cards\CardPrice;

use App\Models\Currency;
use App\Models\Bingo\Rooms\BingoRoom;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los precios de cartones de bingo
 *
 * Representa el precio de un cartón de bingo en una sala y moneda específica.
 * Cada precio está asociado a una sala, una moneda y tiene un usuario creador.
 *
 * @package App\Models\Bingo\Cards\CardPrice
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 *
 * @property string $id ID único del precio
 * @property string $room_id ID de la sala de bingo
 * @property string $creator_user_id ID del usuario creador
 * @property string $currency_id ID de la moneda
 * @property float $card_price Precio del cartón
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 *
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read User $creatorUser Usuario creador del precio
 * @property-read Currency $currency Moneda del precio
 */
class BingoCardPrice extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_card_prices';

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'room_id',
        'creator_user_id',
        'currency_id',
        'card_price',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'card_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoCardPrice>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el usuario creador del precio
     *
     * @return BelongsTo<User, BingoCardPrice>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Relación con la moneda
     *
     * @return BelongsTo<Currency, BingoCardPrice>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Obtiene el precio formateado con símbolo de moneda
     *
     * @return string
     */
    public function getFormattedPrice(): string
    {
        return $this->currency->symbol . ' ' . number_format($this->card_price, 2);
    }

    /**
     * Verifica si el precio es mayor a un monto específico
     *
     * @param float $amount Monto a comparar
     * @return bool
     */
    public function isGreaterThan(float $amount): bool
    {
        return $this->card_price > $amount;
    }

    /**
     * Verifica si el precio es menor a un monto específico
     *
     * @param float $amount Monto a comparar
     * @return bool
     */
    public function isLessThan(float $amount): bool
    {
        return $this->card_price < $amount;
    }

    /**
     * Scope para obtener precios por sala
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Scope para obtener precios por moneda
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
     * Scope para obtener precios en un rango
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minPrice
     * @param float $maxPrice
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePriceRange($query, float $minPrice, float $maxPrice)
    {
        return $query->whereBetween('card_price', [$minPrice, $maxPrice]);
    }
}
