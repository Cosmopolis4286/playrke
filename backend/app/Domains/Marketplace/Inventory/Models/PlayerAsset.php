<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Inventory\Models;

use App\Models\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Instancia de un activo poseída por un jugador. Ver docblock de la
 * migración create_player_assets_table para las reglas de stackable y la
 * referencia blanda a escrows.
 *
 * Todas las transiciones de status pasan por InventoryService — este modelo
 * no expone métodos que muten status directamente para no invitar a otros
 * dominios a saltarse los guards de concurrencia (lockForUpdate + UPDATE...
 * WHERE status = X) que sí aplica InventoryService.
 */
class PlayerAsset extends Model
{
    use SnowflakeId;

    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_CONSUMED = 'CONSUMED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_LOCKED = 'LOCKED';
    public const STATUS_TRANSFERRED = 'TRANSFERRED';
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'asset_definition_id' => 'string',
        'owner_id' => 'string',
        'locked_by_escrow_id' => 'string',
        'quantity' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'asset_definition_id',
        'owner_id',
        'quantity',
        'status',
        'locked_by_escrow_id',
        'acquired_via',
        'acquired_reference',
        'metadata',
        'expires_at',
    ];
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $table = 'player_assets';

    public function assetDefinition() : BelongsTo
    {
        return $this->belongsTo(AssetDefinition::class, 'asset_definition_id');
    }

    public function isAvailable() : bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isLocked() : bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function owner() : BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeOwnedBy($query, string $userId)
    {
        return $query->where('owner_id', $userId);
    }
}
