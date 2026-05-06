<?php

namespace App\Domains\Games\Bingo\Models\Draw;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoDraw extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_draws';

    public const STATUSES      = [1 => 'Pendiente', 2 => 'Activo', 3 => 'Ganador', 4 => 'Completado'];
    public const STATUS_PENDING   = 1;
    public const STATUS_ACTIVE    = 2;
    public const STATUS_WINNING   = 3;
    public const STATUS_COMPLETED = 4;

    protected $fillable = ['room_id', 'chip', 'code', 'round', 'time', 'status'];

    protected $casts = [
        'round'      => 'integer',
        'time'       => 'integer',
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_description', 'is_winning', 'formatted_time'];

    public function room(): BelongsTo { return $this->belongsTo(BingoRoom::class, 'room_id'); }
    public function codeDraw(): BelongsTo { return $this->belongsTo(BingoDrawCode::class, 'room_id', 'room_id'); }

    public function scopeByRoom($query, string $roomId) { return $query->where('room_id', $roomId); }
    public function scopeByStatus($query, int $status) { return $query->where('status', $status); }
    public function scopeByRound($query, int $round) { return $query->where('round', $round); }
    public function scopePending($query) { return $query->where('status', self::STATUS_PENDING); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeWinning($query) { return $query->where('status', self::STATUS_WINNING); }
    public function scopeCompleted($query) { return $query->where('status', self::STATUS_COMPLETED); }
    public function scopeByChip($query, string $chip) { return $query->where('chip', $chip); }
    public function scopeByCode($query, string $code) { return $query->where('code', $code); }
    public function scopeInSequence($query) { return $query->orderBy('time')->orderBy('created_at'); }
    public function scopeByTimeRange($query, int $start, int $end) { return $query->whereBetween('time', [$start, $end]); }

    public function getStatusDescriptionAttribute(): string { return self::STATUSES[$this->status] ?? 'Desconocido'; }
    public function getIsWinningAttribute(): bool { return $this->status === self::STATUS_WINNING; }
    public function getFormattedTimeAttribute(): string { return $this->time ? date('H:i:s', $this->time) : ''; }
    public function getIsActiveAttribute(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function getIsCompletedAttribute(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function getIsPendingAttribute(): bool { return $this->status === self::STATUS_PENDING; }
}
