<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Inventory\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Catálogo de tipos de activo digital. Ver docblock de la migración
 * create_asset_definitions_table para el rol de cada flag.
 */
class AssetDefinition extends Model
{
    use SnowflakeId;

    public const CATEGORY_BOOSTER = 'BOOSTER';
    public const CATEGORY_CONSUMABLE = 'CONSUMABLE';
    public const CATEGORY_COSMETIC = 'COSMETIC';
    public const CATEGORY_EQUIPMENT = 'EQUIPMENT';
    public const CATEGORY_RESOURCE = 'RESOURCE';
    public const CATEGORY_TICKET = 'TICKET';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    private const CACHE_KEY = 'asset_definitions:catalog';
    private const CACHE_TTL = 3600;
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'stackable' => 'boolean',
        'tradable' => 'boolean',
        'transferable' => 'boolean',
        'consumable' => 'boolean',
        'marketplace_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'rarity',
        'stackable',
        'tradable',
        'transferable',
        'consumable',
        'marketplace_enabled',
        'game_group',
        'metadata',
        'status',
        'sort_order',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'asset_definitions';

    /**
     * Catálogo completo cacheado, indexado por code — mismo patrón que
     * MovementType::catalog(), usado por servicios que resuelven flags de
     * una definición con frecuencia sin pagar una query por llamada.
     */
    public static function catalog() : Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => self::query()->get()->keyBy('code'));
    }

    public static function forgetCatalogCache() : void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function canBeListedOnMarketplace() : bool
    {
        return $this->tradable && $this->marketplace_enabled && $this->status === self::STATUS_ACTIVE;
    }

    public function playerAssets() : HasMany
    {
        return $this->hasMany(PlayerAsset::class, 'asset_definition_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
