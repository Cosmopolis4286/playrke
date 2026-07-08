<?php

namespace App\Domains\Games\Bingo\Models\Card;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Currency;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoLotRefundFailure extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_lot_refund_failures';

    protected $fillable = [
        'room_id', 'lot_id', 'user_id', 'currency_id',
        'amount', 'error_message', 'status', 'attempts',
        'last_attempted_at', 'resolved_at',
    ];

    protected $casts = [
        'id'                => 'string',
        'room_id'           => 'string',
        'lot_id'            => 'string',
        'user_id'           => 'string',
        'currency_id'       => 'string',
        'amount'            => 'decimal:4',
        'attempts'          => 'integer',
        'last_attempted_at' => 'datetime',
        'resolved_at'       => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(BingoCardLot::class, 'lot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }
}
