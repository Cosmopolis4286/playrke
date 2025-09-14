<?php

namespace App\Models\Games\Bingo\Rooms;

use App\Models\Games\Bingo\Cards\BingoCard;
use App\Models\Games\Bingo\Cards\CardPrice\BingoCardPrice;
use App\Models\Games\Bingo\Cards\BingoCardLot;
use App\Models\Games\Bingo\Creators\BingoCreator;
use App\Models\Games\Bingo\Creators\BingoCreatorSale;
use App\Models\Games\GameStatus;
use App\Models\Games\GameExchangeRate;
use App\Models\Users\User;
use App\Models\Sellers\SellerRoomLimits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use BradieTilley\Snowflake\Snowflake;

/**
 * Modelo BingoRoom refactorizado
 *
 * Solo contiene:
 * - Configuración de la tabla y propiedades
 * - Relaciones Eloquent
 * - Accessors y Mutators
 * - Boot methods si es necesario
 *
 * NOTA: Las consultas complejas se han movido a BingoRoomRepository
 */
class BingoRoom extends Model
{
    use SoftDeletes;

    // ========================
    // Configuración de la tabla
    // ========================
    protected $table = 'bingo_rooms';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // ========================
    // Campos asignables
    // ========================
    protected $fillable = [
        'encrypted_code',
        'status_id',
        'room_type',
        'creator_user_id',
        'prizes_paid',
        'publication_date',
        'total_players',
        'total_cards',
        'total_sales',
        'rke_commission',
        'room_commission',
        'seller_commission',
        'prize_pool',
        'full_house_prize',
        'serial_prize',
        'closed_at',
        'uuid_code',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'publication_date' => 'datetime',
        'closed_at' => 'datetime',
        'total_sales' => 'decimal:2',
        'rke_commission' => 'decimal:2',
        'room_commission' => 'decimal:2',
        'seller_commission' => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'full_house_prize' => 'decimal:2',
        'serial_prize' => 'decimal:2',
        'total_players' => 'integer',
        'total_cards' => 'integer',
        'prizes_paid' => 'boolean',
    ];

    // ========================
    // Atributos ocultos
    // ========================
    protected $hidden = [
        'deleted_at',
    ];

    // ========================
    // Accessors computados
    // ========================
    protected $appends = ['room_type_description', 'is_closed', 'total_commissions'];

    // ========================
    // Boot method para Snowflake
    // ========================
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BingoRoom $room) {
            if (!$room->id) {
                $room->id = (new Snowflake())->id();
            }
        });
    }

    // ========================
    // Relaciones
    // ========================

    /**
     * Estado de la sala
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(GameStatus::class, 'status_id');
    }

    /**
     * Usuario creador de la sala
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Configuración de la sala
     */
    public function config(): HasMany
    {
        return $this->hasMany(BingoRoomConfig::class, 'room_id', 'id');
    }

    /**
     * Creadores asociados a la sala
     */
    public function bingoCreators(): HasMany
    {
        return $this->hasMany(BingoCreator::class, 'room_id', 'id');
    }

    /**
     * Precios de cartones
     */
    public function cardPrices(): HasMany
    {
        return $this->hasMany(BingoCardPrice::class, 'room_id', 'id');
    }

    /**
     * Lotes de cartones
     */
    public function cardLots(): HasMany
    {
        return $this->hasMany(BingoCardLot::class, 'room_id', 'id');
    }

    /**
     * Cartones de la sala
     */
    public function cards(): HasMany
    {
        return $this->hasMany(BingoCard::class, 'room_id', 'id');
    }

    /**
     * Ventas del creador
     */
    public function creatorSales(): HasMany
    {
        return $this->hasMany(BingoCreatorSale::class, 'room_id', 'id');
    }

    /**
     * Jugadores de la sala
     */
    public function players(): HasMany
    {
        return $this->hasMany(BingoRoomUser::class, 'room_id', 'id');
    }

    /**
     * Límites del vendedor
     */
    public function sellerLimits(): BelongsTo
    {
        return $this->belongsTo(SellerRoomLimits::class, 'id', 'room_id');
    }

    /**
     * Tasas de cambio del juego
     */
    public function exchangeRates(): HasMany
    {
        return $this->hasMany(GameExchangeRate::class, 'room_id', 'id');
    }

    // ========================
    // Accessors
    // ========================

    /**
     * Descripción del tipo de sala
     */
    public function getRoomTypeDescriptionAttribute(): string
    {
        return match($this->room_type) {
            'PV' => 'Público',
            'PB' => 'Privado',
            default => 'Desconocido',
        };
    }

    /**
     * Verificar si la sala está cerrada
     */
    public function getIsClosedAttribute(): bool
    {
        return !is_null($this->closed_at);
    }

    /**
     * Total de comisiones
     */
    public function getTotalCommissionsAttribute(): float
    {
        return $this->rke_commission + $this->room_commission + $this->seller_commission;
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para salas activas
     */
    public function scopeActive($query)
    {
        return $query->whereHas('status', function($q) {
            $q->where('name', 'ACTIVE');
        });
    }

    /**
     * Scope para salas por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('room_type', $type);
    }

    /**
     * Scope para salas con premios pendientes
     */
    public function scopeWithPendingPrizes($query)
    {
        return $query->where('prizes_paid', false)
                    ->where('prize_pool', '>', 0);
    }
}
