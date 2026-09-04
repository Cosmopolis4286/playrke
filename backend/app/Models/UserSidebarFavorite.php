<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSidebarFavorite extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'user_id',
        'item_key',
        'sort_order',
    ];

    protected $casts = [
        'id'      => 'string',
        'user_id' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
