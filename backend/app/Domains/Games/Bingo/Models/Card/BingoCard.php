<?php

namespace App\Domains\Games\Bingo\Models\Card;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Models\Currency;
use App\Models\Roles\Seller;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BingoCard extends Model
{
    use SnowflakeId, SoftDeletes;

    protected $table = 'bingo_cards';

    public const CARD_STATUSES = [
        'active'    => 'Activo',
        'inactive'  => 'Inactivo',
        'winner'    => 'Ganador',
        'cancelled' => 'Cancelado',
    ];

    public const COLUMNS = ['B', 'I', 'N', 'G', 'O'];

    protected $fillable = [
        'room_id', 'creator_user_id', 'seller_user_id', 'seller_type', 'room_user_id',
        'card_lot_id', 'currency_id', 'serial_number', 'card_numbers',
        'card_hash', 'status', 'is_validated', 'validated_at',
    ];

    protected $casts = [
        'card_numbers' => 'array',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user_id');
    }

    public function cardLot(): BelongsTo
    {
        return $this->belongsTo(BingoCardLot::class, 'card_lot_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(BingoCardPrize::class, 'winning_card_id');
    }

    public function getCardMatrix(): array
    {
        return $this->card_numbers ?? [];
    }

    public function getColumnNumbers(string $letter): array
    {
        return $this->card_numbers[strtoupper($letter)] ?? [];
    }

    public function hasNumber(int $number): bool
    {
        foreach (self::COLUMNS as $col) {
            if (in_array($number, $this->card_numbers[$col] ?? [], true)) {
                return true;
            }
        }
        return false;
    }

    public function getCardStatusDescription(): string
    {
        return self::CARD_STATUSES[$this->status] ?? $this->status;
    }

    public function isActive(): bool { return $this->status === 'active'; }
    public function isWinner(): bool { return $this->status === 'winner'; }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeWinners($query) { return $query->where('status', 'winner'); }
    public function scopeValidated($query) { return $query->where('is_validated', true); }

    public function toFrontendFormat(): array
    {
        return [
            'id'           => $this->id,
            'roomId'       => $this->room_id,
            'userId'       => $this->roomUser?->user_id,
            'serialNumber' => $this->serial_number,
            'cardMatrix'   => $this->card_numbers ?? [],
            'isActive'     => $this->status === 1,
            'purchaseDate' => $this->created_at,
            'price'        => 0,
            'currencyId'   => $this->currency_id,
            'lot'          => $this->cardLot ? ['id' => $this->cardLot->id] : null,
        ];
    }
}
