<?php

namespace App\Models\Games\Bingo\Rooms\User;

use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para usuarios de salas de bingo
 */
class BingoRoomUser extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_room_users';

    protected $fillable = [
        'room_id',
        'creator_user_id',
        'ctns',
        'automarker',
        'winner'
    ];

    protected $casts = [
        'ctns' => 'integer',
        'automarker' => 'decimal:2'
    ];

    /**
     * Relación con la sala de bingo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    /**
     * Relación con el usuario creador/participante
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }
}