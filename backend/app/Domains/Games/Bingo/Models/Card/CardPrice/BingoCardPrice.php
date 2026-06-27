<?php

namespace App\Domains\Games\Bingo\Models\Card\CardPrice;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCardPrice extends BaseModel
{
    protected $table = 'bingo_card_prices';

    protected $fillable = [
        'room_id',
        'creator_user_id',
        'currency_id',
        'card_price',
    ];

    protected $casts = [
        'card_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getFormattedPrice(): string
    {
        return trim($this->currency->symbol . ' ' . $this->currency->formatAmount((float) $this->card_price));
    }

    public function isGreaterThan(float $amount): bool { return $this->card_price > $amount; }
    public function isLessThan(float $amount): bool { return $this->card_price < $amount; }

    public function scopeByRoom($query, string $roomId) { return $query->where('room_id', $roomId); }
    public function scopeByCurrency($query, string $currencyId) { return $query->where('currency_id', $currencyId); }
    public function scopePriceRange($query, float $min, float $max) { return $query->whereBetween('card_price', [$min, $max]); }
}
