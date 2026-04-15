<?php

namespace App\Domains\Games\Bingo\Models\Room\User;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para usuarios de salas de bingo
 */
class BingoRoomUser extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_room_users';

    protected $fillable = [
        'room_id',
        'user_id',
        'cards_purchased',
        'automarker',
        'winner'
    ];

    protected $casts = [
        'cards_purchased' => 'integer',
        'automarker' => 'decimal:2'
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }
}
