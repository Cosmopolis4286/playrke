<?php

namespace App\Domains\Games\Bingo\Models\Creator;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Role;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCreator extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_room_creators';

    public const STATUSES = [
        'active'   => 'Activo',
        'inactive' => 'Inactivo',
    ];

    protected $fillable = [
        'room_id', 'user_id', 'creator_commission', 'status',
    ];

    protected $casts = [
        'room_id'       => 'string',
        'user_id'       => 'string',
        'creator_commission' => 'decimal:8',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function room(): BelongsTo { return $this->belongsTo(BingoRoom::class, 'room_id'); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeInactive($query) { return $query->where('status', 'inactive'); }
    public function scopeByRoom($query, string $roomId) { return $query->where('room_id', $roomId); }
    public function scopeByUser($query, string $userId) { return $query->where('user_id', $userId); }

    public function getStatusDescriptionAttribute(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function getIsActiveAttribute(): bool { return $this->status === 'active'; }
}
