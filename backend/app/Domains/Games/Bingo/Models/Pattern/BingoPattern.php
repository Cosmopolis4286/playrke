<?php

namespace App\Domains\Games\Bingo\Models\Pattern;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoPattern extends Model
{
    use SnowflakeId;

    protected $table = 'bingo_patterns';

    public const TYPES    = ['UNIQUE' => 'Único', 'MULTIPLE' => 'Múltiple'];
    public const STATUSES = ['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo'];

    public const TYPE_UNIQUE   = 'UNIQUE';
    public const TYPE_MULTIPLE = 'MULTIPLE';
    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const SYSTEM_NUM_ID_MIN = 1;
    public const SYSTEM_NUM_ID_MAX = 30;

    protected $fillable = ['num_id', 'name', 'default_pattern', 'type_pattern', 'user_id', 'boxes', 'status'];

    protected $casts = [
        'id'              => 'string',
        'default_pattern' => 'string',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    protected $appends = ['type_description', 'status_description', 'boxes_array', 'boxes_count', 'status_badge'];

    public function userOwner(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function scopeByUser($query, string $userId) { return $query->where('user_id', $userId); }
    public function scopeByType($query, string $type) { return $query->where('type_pattern', $type); }
    public function scopeByStatus($query, string $status) { return $query->where('status', $status); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeInactive($query) { return $query->where('status', self::STATUS_INACTIVE); }
    public function scopeUnique($query) { return $query->where('type_pattern', self::TYPE_UNIQUE); }
    public function scopeMultiple($query) { return $query->where('type_pattern', self::TYPE_MULTIPLE); }
    public function scopeByName($query, string $name) { return $query->where('name', 'like', "%{$name}%"); }

    public function getTypeDescriptionAttribute(): string { return self::TYPES[$this->type_pattern] ?? $this->type_pattern ?? ''; }
    public function getStatusDescriptionAttribute(): string { return self::STATUSES[$this->status] ?? $this->status ?? ''; }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE'   => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'BLOCKED'  => '<span class="badge badge-danger">Bloqueado</span>',
            'DELETED'  => '<span class="badge badge-dark">Eliminado</span>',
            default    => '<span class="badge badge-warning">Desconocido</span>',
        };
    }

    public function getBoxesArrayAttribute(): array { return $this->boxes ? array_filter(explode(';', $this->boxes)) : []; }
    public function getBoxesCountAttribute(): int { return count($this->boxes_array); }
    public function getIsActiveAttribute(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function getIsInactiveAttribute(): bool { return $this->status === self::STATUS_INACTIVE; }
    public function getIsUniqueAttribute(): bool { return $this->type_pattern === self::TYPE_UNIQUE; }
    public function getIsMultipleAttribute(): bool { return $this->type_pattern === self::TYPE_MULTIPLE; }

    public static function nextNumIdForUser(string $userId): int
    {
        $max = static::where('user_id', $userId)->where('num_id', '>', self::SYSTEM_NUM_ID_MAX)->max('num_id');
        return $max ? $max + 1 : self::SYSTEM_NUM_ID_MAX + 1;
    }

    public function getFormattedColumns(): array
    {
        if (empty($this->boxes)) return ['type' => $this->type_pattern, 'columns' => []];
        if ($this->is_unique) {
            return ['type' => $this->type_pattern, 'columns' => array_values(array_unique($this->boxes_array))];
        }
        $columns = [];
        foreach ($this->boxes_array as $numId) {
            $numId   = (int) $numId;
            $pattern = static::where('num_id', $numId)
                ->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $this->user_id))
                ->first();
            if ($pattern && $pattern->boxes) {
                $columns[$numId] = array_values(array_unique(array_filter(explode(';', $pattern->boxes))));
            }
        }
        return ['type' => $this->type_pattern, 'columns' => $columns];
    }

    public function isValidBoxesFormat(): bool
    {
        if (empty($this->boxes)) return false;
        foreach ($this->boxes_array as $box) {
            if (!is_numeric($box) || $box < 1 || $box > 75) return false;
        }
        return true;
    }

    public function isValid(): bool
    {
        return !empty($this->name) && !empty($this->type_pattern) && !empty($this->boxes) && $this->isValidBoxesFormat();
    }

    public function getFormattedBoxes(): string { return implode(', ', $this->boxes_array); }
}
