<?php

namespace App\Domains\Games\Bingo\Models\Draw;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoDrawCode extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_draws_code';

    protected $fillable = ['room_id', 'round_id', 'num', 'sym'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['full_code', 'formatted_code'];

    public function room(): BelongsTo { return $this->belongsTo(BingoRoom::class, 'room_id'); }

    public function round(): BelongsTo { return $this->belongsTo(BingoRound::class, 'round_id'); }

    public function scopeByRoom($query, string $roomId) { return $query->where('room_id', $roomId); }
    public function scopeByNumber($query, string $num) { return $query->where('num', $num); }
    public function scopeBySymbol($query, string $sym) { return $query->where('sym', $sym); }
    public function scopeByNumberPattern($query, string $pattern) { return $query->where('num', 'like', $pattern); }
    public function scopeBySymbolPattern($query, string $pattern) { return $query->where('sym', 'like', $pattern); }
    public function scopeUnique($query) { return $query->distinct(['num', 'sym']); }

    public function getFullCodeAttribute(): string { return $this->num . $this->sym; }
    public function getFormattedCodeAttribute(): string { return sprintf('%s-%s', $this->num, $this->sym); }
    public function getIsValidAttribute(): bool { return !empty($this->num) && !empty($this->sym) && $this->isValidFormat(); }

    public function isValidFormat(): bool
    {
        // num = posición en la secuencia (1-based index), sym = número de ficha cantada.
        // Ambos son enteros positivos; el rango máximo es 1-90 para BINGO_90.
        if (!ctype_digit((string) $this->num) || (int) $this->num < 1) return false;
        if (!ctype_digit((string) $this->sym) || (int) $this->sym < 1) return false;
        return true;
    }

    public function isUnique(): bool
    {
        $query = static::where('num', $this->num)->where('sym', $this->sym);
        if ($this->exists) $query->where('id', '!=', $this->id);
        return !$query->exists();
    }
}
