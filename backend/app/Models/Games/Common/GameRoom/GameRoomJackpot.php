<?php

namespace App\Models\Shared\GameRoom;

use App\Models\Commerce\Currency;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoomJackpot extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'currency_id',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
