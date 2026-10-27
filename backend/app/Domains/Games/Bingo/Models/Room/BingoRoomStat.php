<?php

namespace App\Domains\Games\Bingo\Models\Room;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BingoRoomStat extends Model
{
    use SnowflakeId;

    protected $casts = [
        'id' => 'string',
        'room_id' => 'string',
        'total_lots' => 'integer',
        'total_cards' => 'integer',
        'total_players' => 'integer',
        'total_revenue' => 'decimal:4',
        'total_revenue_usd' => 'decimal:4',
        'prize_pool_final' => 'decimal:4',
        'total_chips_drawn' => 'integer',
        'total_rounds' => 'integer',
        'winning_rounds' => 'integer',
        'draw_duration_seconds' => 'integer',
        'calculated_at' => 'datetime',
    ];

    protected $fillable = [
        'room_id',
        'total_lots',
        'total_cards',
        'total_players',
        'total_revenue',
        'total_revenue_usd',
        'prize_pool_final',
        'total_chips_drawn',
        'total_rounds',
        'winning_rounds',
        'draw_duration_seconds',
        'calculated_at',
    ];

    protected $table = 'bingo_room_stats';

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }
}
