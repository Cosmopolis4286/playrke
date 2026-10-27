<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TosVersion extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_DRAFT = 'draft';

    protected $casts = [
        'effective_at' => 'datetime',
    ];

    protected $fillable = [
        'version',
        'title',
        'content_key',
        'summary',
        'status',
        'effective_at',
    ];
    protected $table = 'tos_versions';

    public static function current() : ?self
    {
        return static::where('status', self::STATUS_ACTIVE)
            ->orderByDesc('effective_at')
            ->first();
    }

    public function isActive() : bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
