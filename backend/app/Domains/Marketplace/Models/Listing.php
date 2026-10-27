<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Models;

use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Models\Currency;
use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Publicación de venta de un PlayerAsset. Todas las transiciones de status
 * pasan por ListingService — mismo criterio que PlayerAsset respecto a
 * InventoryService.
 */
class Listing extends Model
{
    use SnowflakeId;
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_RESERVED = 'RESERVED';
    public const STATUS_SOLD = 'SOLD';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'seller_id' => 'string',
        'player_asset_id' => 'string',
        'currency_id' => 'string',
        'quantity' => 'integer',
        'price' => 'float',
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'seller_id',
        'player_asset_id',
        'quantity',
        'price',
        'currency_id',
        'status',
        'expires_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'marketplace_listings';

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function isActive() : bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class, 'listing_id');
    }

    public function playerAsset() : BelongsTo
    {
        return $this->belongsTo(PlayerAsset::class, 'player_asset_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function seller() : BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
