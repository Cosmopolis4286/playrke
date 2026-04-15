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

    protected $table = 'bingo_creator_sales';

    protected $fillable = [
        'room', 'creator_user_id', 'currency', 'sale_amount',
        'rke_commission', 'room_commission', 'seller_commn', 'prize_pool_amount',
    ];

    protected $casts = [
        'sale_amount'       => 'decimal:8',
        'rke_commission'    => 'decimal:8',
        'room_commission'   => 'decimal:8',
        'seller_commn'      => 'decimal:8',
        'prize_pool_amount' => 'decimal:8',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    protected $appends = ['total_commission', 'commission_percentage'];

    public function room(): BelongsTo { return $this->belongsTo(BingoRoom::class, 'room'); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class, 'currency'); }
    public function creatorUser(): BelongsTo { return $this->belongsTo(User::class, 'creator_user_id'); }

    public function scopeByRoom($query, string $roomId) { return $query->where('room', $roomId); }
    public function scopeByCreator($query, string $id) { return $query->where('creator_user_id', $id); }
    public function scopeByCurrency($query, string $currency) { return $query->where('currency', $currency); }
    public function scopeByAmountRange($query, float $min, float $max) { return $query->whereBetween('sale_amount', [$min, $max]); }
    public function scopeByDateRange($query, string $start, string $end) { return $query->whereBetween('created_at', [$start, $end]); }
    public function scopeTopByVolume($query, int $limit = 10) { return $query->orderByDesc('sale_amount')->limit($limit); }

    public function getTotalCommissionAttribute(): float { return $this->rke_commission + $this->room_commission + $this->seller_commn; }

    public function getCommissionPercentageAttribute(): float
    {
        if ($this->sale_amount <= 0) return 0.0;
        return round(($this->total_commission / $this->sale_amount) * 100, 2);
    }

    public function getIsValidAttribute(): bool
    {
        $calculatedTotal = $this->rke_commission + $this->room_commission + $this->seller_commn + $this->prize_pool_amount;
        return abs($this->sale_amount - $calculatedTotal) <= 0.01;
    }
}
