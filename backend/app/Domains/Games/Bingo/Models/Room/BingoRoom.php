<?php

namespace App\Domains\Games\Bingo\Models\Room;

use App\Models\BaseModel;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPrice\BingoCardPrice;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Creator\BingoCreator;
use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Games\Common\Utils\GameStatus;
use App\Models\Pivots\Roles\Seller\SellerRoomLimits;
use App\Models\User;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Models\Games\Common\Utils\GameExchangeRate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo BingoRoom - Dominio Bingo
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
        'creator_user_id',
        'paid_prizes',
        'published_at',
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
        'metrics_by_currency',
    ];

    // ========================
    // Casting de tipos
    // ========================
    protected $casts = [
        'id' => 'string',
        'uuid_code' => 'string',
        'status_id' => 'string',
        'creator_user_id' => 'string',
        'published_at' => 'datetime',
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
        'paid_prizes' => 'string',
        'metrics_by_currency' => \App\Casts\AsForceObjectJson::class,
    ];

    // ========================
    // Atributos ocultos
    // ========================
    protected $hidden = [
        'deleted_at',
    ];

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
     * Configuración de la sala (1:1)
     */
    public function config(): HasOne
    {
        return $this->hasOne(BingoRoomConfig::class, 'room_id', 'id');
    }

    /**
     * Rondas de la sala
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(BingoRound::class, 'room_id', 'id')->orderBy('round');
    }

    public function draws(): HasMany
    {
        return $this->hasMany(BingoDraw::class, 'room_id', 'id');
    }

    public function drawCodes(): HasMany
    {
        return $this->hasMany(BingoDrawCode::class, 'room_id', 'id');
    }

    /**
     * Creadores asociados a la sala
     */
    public function bingoCreators(): HasMany
    {
        return $this->hasMany(BingoCreator::class, 'room_id', 'id');
    }

    /**
     * Bitácora de eventos de dominio de la sala (creación sin horario, activación manual, etc.)
     */
    public function audits(): HasMany
    {
        return $this->hasMany(BingoRoomAudit::class, 'room_id', 'id');
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

    public function cardPrizes(): HasMany
    {
        return $this->hasMany(BingoCardPrize::class, 'room_id', 'id');
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
     * Jugadores de la sala, para preview de prueba social (avatares/iniciales en el lobby).
     * Relación separada de players() para poder aplicar su propio eager-load de `user`
     * sin pisar el constraint por user_id que usa players() en otros contextos.
     */
    public function playersPreview(): HasMany
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
    public function getStatusCodeAttribute(): ?string
    {
        return $this->getRelationValue('status')?->code
            ?? $this->attributes['status'] ?? null;
    }

    /**
     * Texto descriptivo del inicio de la sala para listados:
     * fecha y hora programadas, o "Sin horario (manual)" si requiere activación manual.
     */
    public function getStartInfoAttribute(): string
    {
        $config = $this->getRelationValue('config');

        if (($config?->start_mode ?? 'SCHEDULED') === 'MANUAL') {
            return 'Sin horario (manual)';
        }

        $date = $config?->date?->format('Y-m-d');
        $time = $config?->time;

        if (!$date || !$time) {
            return '—';
        }

        return "{$date} {$time}";
    }

    /**
     * Timezone IANA (ej. 'America/Caracas') usado por getStatusBadgeAttribute()
     * para mostrar la fecha de última actualización en hora local del usuario
     * que ve la tabla — seteado por BingoRoomContextService antes de serializar
     * cada fila (accessors no reciben parámetros, así que se inyecta como
     * propiedad pública). Default 'UTC' si nadie lo setea (ej. en tests).
     */
    public string $displayTimezone = 'UTC';

    /**
     * Último editor real de la sala (tabla audits, evento 'updated'), resuelto
     * y seteado por BingoRoomContextService::resolveLastEditorsForRooms() en
     * una sola query agrupada para todas las filas de la página — evita N+1.
     * Null si la sala nunca se editó vía HTTP autenticado (solo creación).
     *
     * @var array{username: string|null, at: \Illuminate\Support\Carbon}|null
     */
    public ?array $lastEditor = null;

    /**
     * Chip HTML del estado (punto de color + texto) + ícono de info con popover
     * CSS-only (:hover/:focus, sin JS) mostrando fecha de creación, última
     * actualización y último editor. Ver .room-status-chip__info-* en BaseTable.vue.
     */
    public function getStatusBadgeAttribute(): string
    {
        $code = $this->getStatusCodeAttribute();

        [$variant, $label] = match ($code) {
            'ACTIVE'      => ['live', 'Disponible'],
            'IN_PROGRESS' => ['live', 'En Juego'],
            'FINISHED'    => ['finished', 'Finalizada'],
            'REFUND'      => ['warning', 'Reembolso'],
            'OFFLINE'     => ['offline', 'Fuera de Línea'],
            'CANCELLED'   => ['cancelled', 'Cancelada'],
            default       => ['offline', 'No Disponible'],
        };

        $infoHtml = '';
        if ($this->updated_at) {
            $updatedLocal = $this->updated_at->copy()->setTimezone($this->displayTimezone);
            $createdLocal = $this->created_at?->copy()->setTimezone($this->displayTimezone);
            $offset       = $updatedLocal->format('P');

            $rows = [];
            $rows[] = ['Última actualización', $updatedLocal->format('d/m/Y H:i') . ' (UTC' . $offset . ')'];
            $rows[] = [
                'Editado por',
                $this->lastEditor ? ($this->lastEditor['username'] ?? 'Usuario') : 'Sin ediciones registradas',
            ];
            if ($createdLocal) {
                $rows[] = ['Creada el', $createdLocal->format('d/m/Y H:i')];
            }

            $rowsHtml = implode('', array_map(
                fn($row) => '<div class="room-status-popover__row">'
                    . '<span class="room-status-popover__label">' . e($row[0]) . '</span>'
                    . '<span class="room-status-popover__value">' . e($row[1]) . '</span>'
                    . '</div>',
                $rows
            ));

            $infoHtml = '<span class="room-status-chip__info" tabindex="0">'
                . '<i class="fa-solid fa-circle-info"></i>'
                . '<div class="room-status-popover">' . $rowsHtml . '</div>'
                . '</span>';
        }

        return '<div class="room-status-chip-wrap">'
            . '<span class="room-status-chip room-status-chip--' . $variant . '">' . e($label) . '</span>'
            . $infoHtml
            . '</div>';
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
            $q->where('code', 'ACTIVE');
        });
    }

    /**
     * Scope para salas con premios pendientes
     */
    public function scopeWithPendingPrizes($query)
    {
        return $query->where('paid_prizes', 'N')
            ->where('prize_pool', '>', 0);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereHas(
            'config',
            fn($q) => $q->whereDate('date', $date)
        );
    }

    public function scopeAccessibleBy($query, int $creatorUserId)
    {
        return $query->where(function ($q) use ($creatorUserId) {
            $q->where('bingo_rooms.creator_user_id', $creatorUserId)
                ->orWhereHas(
                    'config.roomType',
                    fn($r) => $r->where('code', 'PUBLIC')
                );
        });
    }

    public function scopeFilterRoomType($query, ?string $roomTypeId)
    {
        if (!$roomTypeId) {
            return $query;
        }

        return $query->whereHas(
            'config.roomType',
            fn($q) => $q->where('id', $roomTypeId)
        );
    }

    public function scopeFilterGameMode($query, ?string $gameModeId)
    {
        if (!$gameModeId) {
            return $query;
        }

        return $query->whereHas(
            'config.gameMode',
            fn($q) => $q->where('id', $gameModeId)
        );
    }

    public function scopeFilterPrizePoolType($query, ?string $prizePoolTypeId)
    {
        if (!$prizePoolTypeId) {
            return $query;
        }

        return $query->whereHas(
            'config',
            fn($q) => $q->where('prize_pool_type_id', $prizePoolTypeId)
        );
    }
}
