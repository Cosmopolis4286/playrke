<?php

namespace App\Domains\Games\Bingo\Models\Draw;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoDraw extends Model
{
    use SnowflakeId, HasFactory;

    protected $table = 'bingo_draws';

    public const STATUSES = [
        'WAITING'   => 'Esperando',
        'FINISHED'  => 'Finalizado',
    ];
    public const STATUS_WAITING  = 'WAITING';
    public const STATUS_FINISHED = 'FINISHED';

    protected $fillable = ['room_id', 'round_id', 'chip', 'code', 'time', 'status'];

    protected $casts = [
        'round_id'   => 'string',
        'time'       => 'integer',
        'status'     => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_description', 'is_waiting', 'is_finished', 'formatted_time'];

    public function room(): BelongsTo { return $this->belongsTo(BingoRoom::class, 'room_id'); }
    public function round(): BelongsTo { return $this->belongsTo(BingoRound::class, 'round_id'); }

    public function scopeByRoom($query, string $roomId) { return $query->where('room_id', $roomId); }
    public function scopeByStatus($query, string $status) { return $query->where('status', $status); }
    public function scopeByRound($query, string $roundId) { return $query->where('round_id', $roundId); }
    public function scopeWaiting($query) { return $query->where('status', self::STATUS_WAITING); }
    public function scopeFinished($query) { return $query->where('status', self::STATUS_FINISHED); }
    public function scopeByChip($query, string $chip) { return $query->where('chip', $chip); }
    public function scopeByCode($query, string $code) { return $query->where('code', $code); }
    public function scopeInSequence($query) { return $query->orderBy('time')->orderBy('created_at'); }
    public function scopeByTimeRange($query, int $start, int $end) { return $query->whereBetween('time', [$start, $end]); }

    public function getStatusDescriptionAttribute(): string { return self::STATUSES[$this->status] ?? 'Desconocido'; }
    public function getFormattedTimeAttribute(): string { return $this->time ? date('H:i:s', $this->time) : ''; }
    public function getIsWaitingAttribute(): bool { return $this->status === self::STATUS_WAITING; }
    public function getIsFinishedAttribute(): bool { return $this->status === self::STATUS_FINISHED; }
}
