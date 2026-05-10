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

    public const ROLE_PLAYER = 'PLAYER';
    public const ROLE_SELLER = 'SELLER';
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_PLAYER_SELLER = 'PLAYER_SELLER';

    public const ROLES = [
        'PLAYER' => 'Jugador',
        'SELLER' => 'Vendedor',
        'ADMIN' => 'Administrador',
        'PLAYER_SELLER' => 'Jugador y Vendedor',
    ];

    protected $fillable = [
        'room_id',
        'user_id',
        'role',
        'cards_purchased',
        'automarker',
        'winner'
    ];

    protected $casts = [
        'room_id' => 'string',
        'user_id' => 'string',
        'role' => 'string',
        'cards_purchased' => 'integer',
        'automarker' => 'decimal:2',
        'winner' => 'string'
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPlayer(): bool
    {
        return in_array($this->role, [self::ROLE_PLAYER, self::ROLE_PLAYER_SELLER]);
    }

    public function isSeller(): bool
    {
        return in_array($this->role, [self::ROLE_SELLER, self::ROLE_PLAYER_SELLER]);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
