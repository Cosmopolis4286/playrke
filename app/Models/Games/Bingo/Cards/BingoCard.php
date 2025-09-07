<?php

namespace App\Models\Games\Bingo\Cartons;

use App\Models\Currencies\Currency;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Games\Bingo\Rooms\User\BingoRoomUser;
use App\Models\User\Roles\Player\External\ExternalPlayers;
use App\Models\User\Roles\Seller\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para los cartones de bingo
 * 
 * Representa un cartón individual de bingo con su matriz de números 5x5.
 * Cada cartón pertenece a un lote y contiene los números del juego BINGO.
 * 
 * @package App\Models\Games\Bingo\Cartons
 * @author Sistema PlayRKE
 * @version 1.0
 * 
 * @property int $id ID único del cartón
 * @property string $room_id ID de la sala de bingo
 * @property string $creator_user_id ID del usuario creador
 * @property string|null $seller_id ID del vendedor (opcional)
 * @property string $room_user_id ID del usuario en la sala
 * @property string $card_lot_id ID del lote de cartones
 * @property string $currency_id ID de la moneda utilizada
 * @property string $serial_number Número de serie único del cartón
 * @property int|null $b1 Número en posición B1
 * @property int|null $b2 Número en posición B2
 * @property int|null $b3 Número en posición B3
 * @property int|null $b4 Número en posición B4
 * @property int|null $b5 Número en posición B5
 * @property int|null $i1 Número en posición I1
 * @property int|null $i2 Número en posición I2
 * @property int|null $i3 Número en posición I3
 * @property int|null $i4 Número en posición I4
 * @property int|null $i5 Número en posición I5
 * @property int|null $n1 Número en posición N1
 * @property int|null $n2 Número en posición N2
 * @property int|null $n3 Número en posición N3 (centro libre)
 * @property int|null $n4 Número en posición N4
 * @property int|null $n5 Número en posición N5
 * @property int|null $g1 Número en posición G1
 * @property int|null $g2 Número en posición G2
 * @property int|null $g3 Número en posición G3
 * @property int|null $g4 Número en posición G4
 * @property int|null $g5 Número en posición G5
 * @property int|null $o1 Número en posición O1
 * @property int|null $o2 Número en posición O2
 * @property int|null $o3 Número en posición O3
 * @property int|null $o4 Número en posición O4
 * @property int|null $o5 Número en posición O5
 * @property string $card_hash Hash único del cartón
 * @property string $card_status Estado del cartón
 * @property bool $is_validated Si el cartón ha sido validado
 * @property \Carbon\Carbon|null $validated_at Fecha de validación
 * @property \Carbon\Carbon $created_at Fecha de creación
 * @property \Carbon\Carbon $updated_at Fecha de actualización
 * @property \Carbon\Carbon|null $deleted_at Fecha de eliminación suave
 * 
 * @property-read BingoRoom $room Sala de bingo asociada
 * @property-read User $creatorUser Usuario creador del cartón
 * @property-read Seller|null $seller Vendedor asociado
 * @property-read BingoRoomUser $roomUser Usuario en la sala
 * @property-read BingoCardLot $cardLot Lote del cartón
 * @property-read Currency $currency Moneda utilizada
 * @property-read \Illuminate\Database\Eloquent\Collection|BingoCardPrize[] $prizes Premios del cartón
 */
class BingoCard extends Model
{
    use SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     */
    protected $table = 'bingo_cards';

    /**
     * Estados de cartón disponibles
     */
    public const CARD_STATUSES = [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'winner' => 'Ganador',
        'cancelled' => 'Cancelado',
    ];

    /**
     * Todas las posiciones del cartón de bingo (5x5)
     */
    public const ALL_POSITIONS = [
        'b1', 'b2', 'b3', 'b4', 'b5',
        'i1', 'i2', 'i3', 'i4', 'i5',
        'n1', 'n2', 'n3', 'n4', 'n5',
        'g1', 'g2', 'g3', 'g4', 'g5',
        'o1', 'o2', 'o3', 'o4', 'o5'
    ];

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'room_id',
        'creator_user_id',
        'seller_id',
        'room_user_id',
        'card_lot_id',
        'currency_id',
        'serial_number',
        'b1', 'b2', 'b3', 'b4', 'b5',
        'i1', 'i2', 'i3', 'i4', 'i5',
        'n1', 'n2', 'n3', 'n4', 'n5',
        'g1', 'g2', 'g3', 'g4', 'g5',
        'o1', 'o2', 'o3', 'o4', 'o5',
        'card_hash',
        'card_status',
        'is_validated',
        'validated_at',
    ];

    /**
     * Campos que deben ser convertidos a tipos específicos
     */
    protected $casts = [
        'b1' => 'integer', 'b2' => 'integer', 'b3' => 'integer', 'b4' => 'integer', 'b5' => 'integer',
        'i1' => 'integer', 'i2' => 'integer', 'i3' => 'integer', 'i4' => 'integer', 'i5' => 'integer',
        'n1' => 'integer', 'n2' => 'integer', 'n3' => 'integer', 'n4' => 'integer', 'n5' => 'integer',
        'g1' => 'integer', 'g2' => 'integer', 'g3' => 'integer', 'g4' => 'integer', 'g5' => 'integer',
        'o1' => 'integer', 'o2' => 'integer', 'o3' => 'integer', 'o4' => 'integer', 'o5' => 'integer',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con la sala de bingo
     * 
     * @return BelongsTo<BingoRoom, BingoCard>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el usuario creador del cartón
     * 
     * @return BelongsTo<User, BingoCard>
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Relación con el vendedor (opcional)
     * 
     * @return BelongsTo<Seller, BingoCard>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Relación con el usuario en la sala
     * 
     * @return BelongsTo<BingoRoomUser, BingoCard>
     */
    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user_id');
    }

    /**
     * Relación con el lote de cartones
     * 
     * @return BelongsTo<BingoCardLot, BingoCard>
     */
    public function cardLot(): BelongsTo
    {
        return $this->belongsTo(BingoCardLot::class, 'card_lot_id');
    }

    /**
     * Relación con la moneda utilizada
     * 
     * @return BelongsTo<Currency, BingoCard>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con los premios del cartón
     * 
     * @return HasMany<BingoCardPrize>
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(BingoCardPrize::class, 'winning_card_id');
    }

    /**
     * Obtener la matriz completa del cartón como array
     * 
     * @return array<string, int|null>
     */
    public function getCardMatrix(): array
    {
        $matrix = [];
        foreach (self::ALL_POSITIONS as $position) {
            $matrix[$position] = $this->$position;
        }
        return $matrix;
    }

    /**
     * Obtener los números de una columna específica
     * 
     * @param string $letter Letra de la columna (B, I, N, G, O)
     * @return array<int, int|null>
     */
    public function getColumnNumbers(string $letter): array
    {
        $letter = strtolower($letter);
        return [
            $this->{$letter . '1'},
            $this->{$letter . '2'},
            $this->{$letter . '3'},
            $this->{$letter . '4'},
            $this->{$letter . '5'},
        ];
    }

    /**
     * Verificar si el cartón tiene un número específico
     * 
     * @param int $number Número a buscar
     * @return bool
     */
    public function hasNumber(int $number): bool
    {
        foreach (self::ALL_POSITIONS as $position) {
            if ($this->$position === $number) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener la descripción del estado del cartón
     * 
     * @return string
     */
    public function getCardStatusDescription(): string
    {
        return self::CARD_STATUSES[$this->card_status] ?? $this->card_status;
    }

    /**
     * Verificar si el cartón está activo
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->card_status === 'active';
    }

    /**
     * Verificar si el cartón es ganador
     * 
     * @return bool
     */
    public function isWinner(): bool
    {
        return $this->card_status === 'winner';
    }

    /**
     * Marcar el cartón como ganador
     * 
     * @return bool
     */
    public function markAsWinner(): bool
    {
        $this->card_status = 'winner';
        return $this->save();
    }

    /**
     * Validar el cartón
     * 
     * @return bool
     */
    public function validate(): bool
    {
        $this->is_validated = true;
        $this->validated_at = now();
        return $this->save();
    }

    /**
     * Obtener el último cartón creado en una sala
     * 
     * @param string $roomId ID de la sala
     * @return BingoCard|null
     */
    public static function getLastCardCreatedInRoom(string $roomId): ?BingoCard
    {
        return self::where('room_id', $roomId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Obtener cartones por jugador en una sala
     * 
     * @param string $roomId ID de la sala
     * @param string $roomUserId ID del usuario en la sala
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCardsByPlayerInRoom(string $roomId, string $roomUserId)
    {
        return self::where('room_id', $roomId)
            ->where('room_user_id', $roomUserId)
            ->select(['id', 'serial_number'] + self::ALL_POSITIONS)
            ->get();
    }

    /**
     * Contar cartones en una sala
     * 
     * @param string $roomId ID de la sala
     * @return int
     */
    public static function getCardCountInRoom(string $roomId): int
    {
        return self::where('room_id', $roomId)->count();
    }

    /**
     * Verificar si existe un hash de cartón en una sala
     * 
     * @param string $roomId ID de la sala
     * @param string $hash Hash del cartón
     * @return bool
     */
    public static function existsCardHashInRoom(string $roomId, string $hash): bool
    {
        return self::where('room_id', $roomId)
            ->where('card_hash', $hash)
            ->exists();
    }

    /**
     * Scope para obtener solo cartones activos
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('card_status', 'active');
    }

    /**
     * Scope para obtener solo cartones ganadores
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWinners($query)
    {
        return $query->where('card_status', 'winner');
    }

    /**
     * Scope para obtener cartones validados
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }
}
