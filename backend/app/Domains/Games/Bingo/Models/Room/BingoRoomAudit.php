<?php

namespace App\Domains\Games\Bingo\Models\Room;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de eventos de dominio sobre salas de bingo
 * (creación sin horario, activación manual, etc.).
 */
class BingoRoomAudit extends Model
{
    use SnowflakeId;

    public const EVENT_CREATED_UNSCHEDULED = 'created_unscheduled';
    public const EVENT_MANUALLY_ACTIVATED = 'manually_activated';
    public const EVENT_START_MODE_CHANGED = 'start_mode_changed';

    protected $casts = [
        'room_id' => 'string',
        'user_id' => 'string',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'room_id',
        'user_id',
        'event',
        'actor_role',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $table = 'bingo_room_audits';

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
