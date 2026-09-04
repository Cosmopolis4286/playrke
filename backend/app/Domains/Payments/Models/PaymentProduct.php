<?php

declare(strict_types=1);

namespace App\Domains\Payments\Models;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pack/consumible comprable con USDT-TRC20. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 4 — reutiliza AssetDefinition
 * (Marketplace\Inventory) como "qué se entrega"; esta tabla solo fija precio
 * y cantidad de la oferta comercial.
 */
class PaymentProduct extends Model
{
    use SnowflakeId;

    protected $table = 'payment_products';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'code',
        'name',
        'asset_definition_id',
        'asset_quantity',
        'price_usdt',
        'status',
    ];

    protected $casts = [
        'id'                  => 'string',
        'asset_definition_id' => 'string',
        'asset_quantity'      => 'integer',
        'price_usdt'          => 'float',
    ];

    public function assetDefinition(): BelongsTo
    {
        return $this->belongsTo(AssetDefinition::class, 'asset_definition_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
