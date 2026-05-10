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

    public const ALL_POSITIONS = [
        'b1', 'b2', 'b3', 'b4', 'b5',
        'i1', 'i2', 'i3', 'i4', 'i5',
        'n1', 'n2', 'n3', 'n4', 'n5',
        'g1', 'g2', 'g3', 'g4', 'g5',
        'o1', 'o2', 'o3', 'o4', 'o5',
    ];

    protected $fillable = [
        'room_id', 'creator_user_id', 'seller_user_id', 'seller_type', 'room_user_id',
        'card_lot_id', 'currency_id', 'serial_number', 'card_numbers',
        'b1', 'b2', 'b3', 'b4', 'b5',
        'i1', 'i2', 'i3', 'i4', 'i5',
        'n1', 'n2', 'n3', 'n4', 'n5',
        'g1', 'g2', 'g3', 'g4', 'g5',
        'o1', 'o2', 'o3', 'o4', 'o5',
        'card_hash', 'status', 'is_validated', 'validated_at',
    ];

    protected $casts = [
        'b1' => 'integer', 'b2' => 'integer', 'b3' => 'integer', 'b4' => 'integer', 'b5' => 'integer',
        'i1' => 'integer', 'i2' => 'integer', 'i3' => 'integer', 'i4' => 'integer', 'i5' => 'integer',
        'n1' => 'integer', 'n2' => 'integer', 'n3' => 'integer', 'n4' => 'integer', 'n5' => 'integer',
        'g1' => 'integer', 'g2' => 'integer', 'g3' => 'integer', 'g4' => 'integer', 'g5' => 'integer',
        'o1' => 'integer', 'o2' => 'integer', 'o3' => 'integer', 'o4' => 'integer', 'o5' => 'integer',
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
        $matrix = [];
        foreach (self::ALL_POSITIONS as $position) {
            $matrix[$position] = $this->$position;
        }
        return $matrix;
    }

    public function getColumnNumbers(string $letter): array
    {
        $letter = strtolower($letter);
        return [
            $this->{$letter . '1'}, $this->{$letter . '2'}, $this->{$letter . '3'},
            $this->{$letter . '4'}, $this->{$letter . '5'},
        ];
    }

    public function hasNumber(int $number): bool
    {
        foreach (self::ALL_POSITIONS as $position) {
            if ($this->$position === $number) return true;
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
            'cardMatrix'   => [
                'B' => [$this->b1, $this->b2, $this->b3, $this->b4, $this->b5],
                'I' => [$this->i1, $this->i2, $this->i3, $this->i4, $this->i5],
                'N' => [$this->n1, $this->n2, null, $this->n4, $this->n5],
                'G' => [$this->g1, $this->g2, $this->g3, $this->g4, $this->g5],
                'O' => [$this->o1, $this->o2, $this->o3, $this->o4, $this->o5],
            ],
            'isActive'    => $this->status === 'active',
            'purchaseDate' => $this->created_at,
            'price'        => 0,
            'currencyId'   => $this->currency_id,
            'lot'          => $this->cardLot ? ['id' => $this->cardLot->id, 'serial' => $this->cardLot->serial ?? ''] : null,
        ];
    }
}
