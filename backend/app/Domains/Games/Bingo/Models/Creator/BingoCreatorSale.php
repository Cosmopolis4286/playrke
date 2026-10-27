<?php

namespace App\Domains\Games\Bingo\Models\Creator;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Currency;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCreatorSale extends Model
{
    use SnowflakeId;

    protected $appends = ['total_commission', 'commission_percentage'];

    protected $casts = [
        'sale_amount' => 'decimal:8',
        'rke_commission' => 'decimal:8',
        'room_commission' => 'decimal:8',
        'seller_commission' => 'decimal:8',
        'subsidiary_commission' => 'decimal:8',
        'commerce_commission' => 'decimal:8',
        'jackpot_contribution' => 'decimal:8',
        'prize_pool_amount' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'room_id', 'creator_user_id', 'currency_id', 'sale_amount',
        'rke_commission', 'room_commission', 'seller_commission', 'subsidiary_commission', 'commerce_commission',
        'jackpot_contribution', 'prize_pool_amount',
    ];

    protected $table = 'bingo_creator_sales';

    public function creatorUser() : BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getCommissionPercentageAttribute() : float
    {
        if ($this->sale_amount <= 0) {
            return 0.0;
        }

        return round(($this->total_commission / $this->sale_amount) * 100, 2);
    }

    public function getIsValidAttribute() : bool
    {
        // venta = RKE + creador + red comercial (vendedor + agencia + comercializadora) + jackpot + premios
        $calculatedTotal = $this->total_commission
            + ($this->jackpot_contribution ?? 0) + $this->prize_pool_amount;

        return abs($this->sale_amount - $calculatedTotal) <= 0.01;
    }

    public function getTotalCommissionAttribute() : float
    {
        return (float) $this->rke_commission + (float) $this->room_commission + (float) $this->seller_commission
            + (float) ($this->subsidiary_commission ?? 0) + (float) ($this->commerce_commission ?? 0);
    }

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function scopeByAmountRange($query, float $min, float $max)
    {
        return $query->whereBetween('sale_amount', [$min, $max]);
    }

    public function scopeByCreator($query, string $id)
    {
        return $query->where('creator_user_id', $id);
    }

    public function scopeByCurrency($query, string $currencyId)
    {
        return $query->where('currency_id', $currencyId);
    }

    public function scopeByDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeTopByVolume($query, int $limit = 10)
    {
        return $query->orderByDesc('sale_amount')->limit($limit);
    }
}
