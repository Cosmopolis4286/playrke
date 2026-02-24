<?php

namespace App\Models\Games\Bingo\Card\CardPrice;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\BingoRoomAutomatic;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los borradores de precios de cartones de bingo
 *
 * Representa un borrador de precios que contiene m\u00faltiples monedas y sus respectivos
 * precios para una sala de bingo o sala autom\u00e1tica. Los borradores pueden estar
 * activos o ejecutados.
 *
 * @package App\Models\Games\Bingo\Card\CardPrice
 * @author Sistema de Gesti\u00f3n de Bingo
 * @version 1.0
 *
 * @property string $id ID \u00fanico del borrador
 * @property string|null $bingo_room_id ID de la sala de bingo (opcional)
 * @property string|null $automatic_bingo_room_id ID de la sala autom\u00e1tica (opcional)
 * @property string $creator_user_id ID del usuario creador
 * @property array $currencies Array de c\u00f3digos de monedas
 * @property array $card_prices Array asociativo de precios por moneda
 * @property string $status Estado del borrador
 * @property \Carbon\Carbon $created_at Fecha de creaci\u00f3n
 * @property \Carbon\Carbon $updated_at Fecha de actualizaci\u00f3n
 *
 * @property-read BingoRoom|null $bingoRoom Sala de bingo asociada
 * @property-read BingoRoomAutomatic|null $automaticBingoRoom Sala autom\u00e1tica asociada
 * @property-read User $creatorUser Usuario creador del borrador
 */
class BingoCardPriceDraft extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_card_price_automatic';

    /**
     * Estados de borrador disponibles
     */
    public const STATUSES = [
        'ACTIVE' => 'Activo',
        'EXECUTED' => 'Ejecutado',
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'bingo_room_id',
        'automatic_bingo_room_id',
        'creator_user_id',
        'currencies',
        'card_prices',
        'status',
    ];

    /**
     * Campos que deben ser convertidos a tipos espec\u00edficos
     */
    protected $casts = [
        'currencies' => 'array',
        'card_prices' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relaci\u00f3n con la sala de bingo
     *
     * @return BelongsTo<BingoRoom, BingoCardPriceDraft>
     */
    public function bingoRoom(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'bingo_room_id');
    }

    /**
     * Relaci\u00f3n con la sala autom\u00e1tica de bingo
     *
     * @return BelongsTo<BingoRoomAutomatic, BingoCardPriceDraft>
     */
    public function automaticBingoRoom(): BelongsTo
    {
        return $this->belongsTo(BingoRoomAutomatic::class, 'automatic_bingo_room_id');
    }

    /**
     * Relaci\u00f3n con el usuario creador del borrador
     *
     * @return BelongsTo<User, BingoCardPriceDraft>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Obtiene la descripci\u00f3n del estado del borrador
     *
     * @return string
     */
    public function getStatusDescription(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Verifica si el borrador est\u00e1 activo
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Verifica si el borrador ha sido ejecutado
     *
     * @return bool
     */
    public function isExecuted(): bool
    {
        return $this->status === 'EXECUTED';
    }

    /**
     * Obtiene el precio de una moneda espec\u00edfica
     *
     * @param string $currencyCode C\u00f3digo de la moneda
     * @return float|null Precio encontrado o null
     */
    public function getPriceForCurrency(string $currencyCode): ?float
    {
        if (!$this->card_prices || !isset($this->card_prices[$currencyCode])) {
            return null;
        }

        return floatval($this->card_prices[$currencyCode]);
    }

    /**
     * Verifica si el borrador contiene una moneda espec\u00edfica
     *
     * @param string $currencyCode C\u00f3digo de la moneda
     * @return bool
     */
    public function hasCurrency(string $currencyCode): bool
    {
        return $this->currencies && in_array($currencyCode, $this->currencies);
    }

    /**
     * Obtiene el n\u00famero de monedas en el borrador
     *
     * @return int
     */
    public function getCurrencyCount(): int
    {
        return $this->currencies ? count($this->currencies) : 0;
    }

    /**
     * Obtiene todos los precios formateados
     *
     * @return array Array con precios formateados
     */
    public function getFormattedPrices(): array
    {
        if (!$this->card_prices) {
            return [];
        }

        $formatted = [];
        foreach ($this->card_prices as $currency => $price) {
            $formatted[$currency] = number_format(floatval($price), 2);
        }

        return $formatted;
    }

    /**
     * Scope para obtener solo borradores activos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Scope para obtener solo borradores ejecutados
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExecuted($query)
    {
        return $query->where('status', 'EXECUTED');
    }

    /**
     * Scope para obtener borradores por sala de bingo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBingoRoom($query, string $roomId)
    {
        return $query->where('bingo_room_id', $roomId);
    }

    /**
     * Scope para obtener borradores por sala autom\u00e1tica
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $automaticRoomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAutomaticRoom($query, string $automaticRoomId)
    {
        return $query->where('automatic_bingo_room_id', $automaticRoomId);
    }
}
