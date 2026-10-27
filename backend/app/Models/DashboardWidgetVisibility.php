<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidgetVisibility extends Model
{
    use SnowflakeId;

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    protected $fillable = [
        'item_key',
        'role_id',
        'is_visible',
    ];

    public function role() : BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
