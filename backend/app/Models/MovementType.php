<?php

namespace App\Models;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Catálogo dinámico de tipos de movimiento de billetera, gestionable por
 * admin/superadmin. Reemplaza el antiguo ENUM fijo de wallet_movements.type.
 *
 * is_credit declara si el tipo suma (true) o resta (false) saldo de la
 * wallet — es la única fuente de verdad para WalletMovement::isCredit()/
 * isDebit(). requires_approval declara si el tipo pasa por el flujo
 * PENDING -> approve/reject.
 */
class MovementType extends Model
{
    use SnowflakeId;

    protected $table = 'movement_types';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    private const CACHE_KEY = 'movement_types:catalog';

    protected $fillable = [
        'code',
        'label',
        'description',
        'icon',
        'color',
        'is_credit',
        'requires_approval',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'is_credit' => 'boolean',
        'requires_approval' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(WalletMovement::class, 'type', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Catálogo completo cacheado, indexado por code. Se llama desde
     * WalletMovement::isCredit()/isDebit() sobre instancias que no siempre
     * vienen de una query con eager-load, por eso se cachea el catálogo
     * entero en vez de depender de la relación.
     */
    public static function catalog(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->get()->keyBy('code'));
    }

    public static function forgetCatalogCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
