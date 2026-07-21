<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameParticipation extends Model
{
    use SnowflakeId;

    protected $table = 'game_participations';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public const GAME_BINGO  = 'bingo';
    public const GAME_TRIVIA = 'trivia';

    protected $fillable = [
        'user_id',
        'game',
        'room_id',
        'won',
        'xp_awarded',
    ];

    protected $casts = [
        'won'        => 'boolean',
        'xp_awarded' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
