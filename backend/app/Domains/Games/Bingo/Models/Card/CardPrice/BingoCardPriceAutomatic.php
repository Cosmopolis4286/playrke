<?php

namespace App\Domains\Games\Bingo\Models\Card\CardPrice;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCardPriceAutomatic extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_card_price_automatic';

    public const STATUSES = [
        'ACTIVE'   => 'Activo',
        'EXECUTED' => 'Ejecutado',
    ];

    protected $fillable = [
        'bingo_room_id', 'automatic_bingo_room_id', 'creator_user_id',
        'currencies', 'card_prices', 'status',
    ];

    protected $casts = [
        'currencies'  => 'array',
        'card_prices' => 'array',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function bingoRoom(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'bingo_room_id');
    }

    public function automaticBingoRoom(): BelongsTo
    {
        return $this->belongsTo(BingoRoomAutomatic::class, 'automatic_bingo_room_id');
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function getStatusDescription(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function isActive(): bool { return $this->status === 'ACTIVE'; }
    public function isExecuted(): bool { return $this->status === 'EXECUTED'; }

    public function getPriceForCurrency(string $currencyCode): ?float
    {
        return isset($this->card_prices[$currencyCode]) ? floatval($this->card_prices[$currencyCode]) : null;
    }

    public function hasCurrency(string $currencyCode): bool
    {
        return $this->currencies && in_array($currencyCode, $this->currencies);
    }

    public function getCurrencyCount(): int { return $this->currencies ? count($this->currencies) : 0; }

    public function getFormattedPrices(): array
    {
        if (!$this->card_prices) return [];
        $formatted = [];
        foreach ($this->card_prices as $currency => $price) {
            $formatted[$currency] = number_format(floatval($price), 2);
        }
        return $formatted;
    }

    public function scopeActive($query) { return $query->where('status', 'ACTIVE'); }
    public function scopeExecuted($query) { return $query->where('status', 'EXECUTED'); }
    public function scopeByBingoRoom($query, string $roomId) { return $query->where('bingo_room_id', $roomId); }
    public function scopeByAutomaticRoom($query, string $id) { return $query->where('automatic_bingo_room_id', $id); }
}
