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
    use HasFactory, SnowflakeId;
    public const STATUS_FINISHED = 'FINISHED';
    public const STATUS_WAITING = 'WAITING';

    public const STATUSES = [
        'WAITING' => 'Esperando',
        'FINISHED' => 'Finalizado',
    ];

    protected $appends = ['status_description', 'is_waiting', 'is_finished', 'formatted_time'];

    protected $casts = [
        'round_id' => 'string',
        'time' => 'integer',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = ['room_id', 'round_id', 'chip', 'code', 'time', 'status'];

    protected $table = 'bingo_draws';

    public function getFormattedTimeAttribute() : string
    {
        return $this->time ? date('H:i:s', $this->time) : '';
    }

    public function getIsFinishedAttribute() : bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function getIsWaitingAttribute() : bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function getStatusDescriptionAttribute() : string
    {
        return self::STATUSES[$this->status] ?? 'Desconocido';
    }

    public function room() : BelongsTo
    {
        return $this->belongsTo(BingoRoom::class, 'room_id');
    }

    public function round() : BelongsTo
    {
        return $this->belongsTo(BingoRound::class, 'round_id');
    }

    public function scopeByChip($query, string $chip)
    {
        return $query->where('chip', $chip);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    public function scopeByRoom($query, string $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeByRound($query, string $roundId)
    {
        return $query->where('round_id', $roundId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTimeRange($query, int $start, int $end)
    {
        return $query->whereBetween('time', [$start, $end]);
    }

    public function scopeFinished($query)
    {
        return $query->where('status', self::STATUS_FINISHED);
    }

    public function scopeInSequence($query)
    {
        return $query->orderBy('time')->orderBy('created_at')->orderBy('id');
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }
}
