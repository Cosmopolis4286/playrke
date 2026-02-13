<?php

namespace App\Models\Games\Bingo\Room;

use App\Models\BaseModel;
use App\Models\Games\Bingo\Cards\BingoCard;
use App\Models\Games\Bingo\Cards\BingoCardLot;
use App\Models\Games\Bingo\Cards\CardPrice\BingoCardPrice;
use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Bingo\Creator\BingoCreatorSale;
use App\Models\Games\Bingo\Room\User\BingoRoomUser;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Roles\SellerRoomLimits;
use App\Models\User;
use App\Models\Games\Common\Utils\GameExchangeRate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
class BingoRoom extends BaseModel
{

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
        'finished_at',
        'uuid_code',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'id' => 'string',
        'uuid_code' => 'string',
        'status_id' => 'string',
        'creator_user_id' => 'string',
        'publication_date' => 'datetime',
        'finished_at' => 'datetime',
        'total_sales' => 'decimal:2',
        'rke_commission' => 'decimal:2',
        'room_commission' => 'decimal:2',
        'seller_commission' => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'full_house_prize' => 'decimal:2',
        'serial_prize' => 'decimal:2',
        'total_players' => 'integer',
        'total_cards' => 'integer',
        'prizes_paid' => 'string',
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
        return match ($this->room_type) {
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
        return !is_null($this->finished_at);
    }

    /**
     * Total de comisiones
     */
    public function getTotalCommissionsAttribute(): float
    {
        return $this->rke_commission + $this->room_commission + $this->seller_commission;
    }

    /**
     * Valor string del estado (desde la relación game_status)
     */
    public function getStatusValueAttribute(): ?string
    {
        return $this->status?->value;
    }

    /**
     * Badge HTML del estado
     */
    public function getStatusBadgeAttribute(): string
    {
        $value = $this->status_value;

        return match ($value) {
            'AVAILABLE'   => '<span class="badge badge-success">Disponible</span>',
            'IN_PROGRESS' => '<span class="badge badge-primary">En Juego</span>',
            'FINISHED'    => '<span class="badge badge-info">Finalizada</span>',
            'REFUND'      => '<span class="badge badge-warning">Reembolso</span>',
            'OFFLINE'     => '<span class="badge badge-secondary">Fuera de Línea</span>',
            default       => '<span class="badge badge-dark">N/D</span>',
        };
    }

    // ========================
    // Scopes útiles
    // ========================

    /**
     * Scope para salas activas
     */
    public function scopeActive($query)
    {
        return $query->whereHas('status', function ($q) {
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
        return $query->where('prizes_paid', 'N')
            ->where('prize_pool', '>', 0);
    }
}
