<?php

namespace App\Domains\Games\Bingo\Models\Creator;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO RESTAURADO - Tabla necesaria
 *
 * Relación PIVOT entre creadores (users) y salas (bingo_rooms).
 * Permite que un creador participe en múltiples salas (propias o ajenas)
 * con comisiones específicas por sala.
 */
class BingoCreator extends Model
{
    use SnowflakeId;

    public const STATUSES = [
        'ACTIVE' => 'Activo',
        'INACTIVE' => 'Inactivo',
    ];

    protected $casts = [
        'room_id' => 'string',
        'user_id' => 'string',
        'creator_commission' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'room_id', 'user_id', 'creator_commission', 'status',
    ];

    protected $table = 'bingo_room_creators';

    public function getIsActiveAttribute() : bool
    {
        return $this->status === 'ACTIVE';
    }

    // Accesores
    public function getStatusDescriptionAttribute() : string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
