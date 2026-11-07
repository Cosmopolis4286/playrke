<?php

namespace App\Domains\Games\Bingo\Models\Card;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Currency;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BingoCardLot extends Model
{
    use SnowflakeId, SoftDeletes;

    protected $casts = [
        'subsidiary_id' => 'string',
        'commerce_id' => 'string',
        'subsidiary_commission_amount' => 'decimal:2',
        'commerce_commission_amount' => 'decimal:2',
        'card_count' => 'integer',
        'card_price' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'sale_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'financial_breakdown' => 'array',
        'status' => 'string',
        'has_custom_cards' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'room_id', 'user_id', 'seller_user_id', 'seller_type', 'room_user_id', 'currency_id',
        'card_count', 'card_price', 'exchange_rate', 'sale_amount', 'bonus_amount', 'commission_amount', 'status', 'has_custom_cards',
        'financial_breakdown',
        'subsidiary_id', 'commerce_id', 'subsidiary_commission_amount', 'commerce_commission_amount',
    ];

    protected $table = 'bingo_card_lots';

    protected static function booted() : void
    {
        static::updated(function(self $lot) : void {
            if ($lot->wasChanged('status')) {
                app(\App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomMetricsSynchronizer::class)
                    ->synchronize((string) $lot->room_id);
            }
        });

        static::deleted(function(self $lot) : void {
            app(\App\Domains\Games\Bingo\Services\Room\Metrics\BingoRoomMetricsSynchronizer::class)
                ->synchronize((string) $lot->room_id);
        });
    }

    public function cards() : HasMany
    {
        return $this->hasMany(BingoCard::class, 'card_lot_id');
    }

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getTotalAmountWithCommission() : float
    {
        return $this->sale_amount + ($this->commission_amount ?? 0);
    }

    public function payments() : HasMany
    {
        return $this->hasMany(\App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment::class, 'card_lot_id');
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
        return $query->where('status', 'ACTIVE');
    }

    public function scopeBySeller($query, string $sellerId)
    {
        return $query->where('seller_user_id', $sellerId);
    }

    public function seller() : BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
