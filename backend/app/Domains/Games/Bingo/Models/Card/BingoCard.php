<?php

namespace App\Domains\Games\Bingo\Models\Card;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Currency;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BingoCard extends Model
{
    use SnowflakeId, SoftDeletes;

    public const CARD_STATUSES = [
        1 => 'Activo',
        2 => 'Inactivo',
        3 => 'Ganador',
        4 => 'Cancelado',
    ];

    public const COLUMNS = ['B', 'I', 'N', 'G', 'O'];

    public const STATUS_ACTIVE = 1;
    public const STATUS_CANCELLED = 4;
    public const STATUS_INACTIVE = 2;
    public const STATUS_WINNER = 3;

    protected $casts = [
        'status' => 'integer',
        'card_numbers' => 'array',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'is_custom_card' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'room_id', 'creator_user_id', 'seller_user_id', 'seller_type', 'room_user_id',
        'card_lot_id', 'currency_id', 'serial_number', 'card_numbers',
        'card_hash', 'status', 'is_validated', 'validated_at', 'is_custom_card',
    ];

    protected $table = 'bingo_cards';

    public function cardLot() : BelongsTo
    {
        return $this->belongsTo(BingoCardLot::class, 'card_lot_id');
    }

    public function creatorUser() : BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getCardMatrix() : array
    {
        return $this->card_numbers ?? [];
    }

    public function getCardStatusDescription() : string
    {
        return self::CARD_STATUSES[(int) $this->status] ?? (string) $this->status;
    }

    public function getColumnNumbers(string $letter) : array
    {
        return $this->card_numbers[strtoupper($letter)] ?? [];
    }

    public function hasNumber(int $number) : bool
    {
        // Itera las claves reales del JSON en lugar de COLUMNS fijo — soporta BINGO_90
        // (columnas '1'..'9') y cualquier variante futura sin modificar este método.
        foreach (array_keys($this->card_numbers ?? []) as $col) {
            if (in_array($number, $this->card_numbers[$col], true)) {
                return true;
            }
        }

        return false;
    }

    public function isActive() : bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    public function isWinner() : bool
    {
        return (int) $this->status === self::STATUS_WINNER;
    }

    public function prizes() : HasMany
    {
        return $this->hasMany(BingoCardPrize::class, 'winning_card_id');
    }

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function roomUser() : BelongsTo
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_custom_card', true);
    }

    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }

    public function scopeWinners($query)
    {
        return $query->where('status', self::STATUS_WINNER);
    }

    /**
     * Excluye cartones cuyo lote tiene un pago aún no confirmado (PENDING) o
     * rechazado (REJECTED) en bingo_card_payments — mismo criterio que la vista
     * del jugador (BingoGameService) y la verificación manual de bingo: un
     * cartón que el sistema todavía no confirmó como pagado no participa ni
     * gana en el sorteo. NOT EXISTS (y no NOT IN) para que los cartones sin
     * card_lot_id no queden excluidos por la semántica de NULL.
     */
    public function scopeWithoutUnconfirmedPayment($query)
    {
        return $query->whereNotExists(
            fn ($q) => $q->selectRaw('1')
                ->from('bingo_card_payments')
                ->whereColumn('bingo_card_payments.card_lot_id', 'bingo_cards.card_lot_id')
                ->whereIn('bingo_card_payments.payment_status', ['PENDING', 'REJECTED']),
        );
    }

    public function seller() : BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function toFrontendFormat() : array
    {
        return [
            'id' => (string) $this->id,
            'roomId' => (string) $this->room_id,
            'userId' => $this->roomUser?->user_id,
            'serialNumber' => $this->serial_number,
            'cardMatrix' => $this->card_numbers ?? [],
            'isActive' => (int) $this->status === self::STATUS_ACTIVE,
            'isCustomCard' => (bool) $this->is_custom_card,
            'purchaseDate' => $this->created_at,
            'price' => 0,
            'currencyId' => $this->currency_id,
            'lot' => $this->cardLot ? ['id' => $this->cardLot->id] : null,
        ];
    }
}
