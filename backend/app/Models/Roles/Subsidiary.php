<?php

namespace App\Models\Roles;

use App\Models\BaseModel;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para sucursales/agencias (jerarquía real: Comercializadora →
 * Agencia → Taquilla → Jugador). Antes solo existía como rol Spatie sin
 * entidad de dominio (ver docs/ROLE_HIERARCHY.md §4, desactualizado por
 * este cambio) — la tabla `subsidiaries` ya existía sin modelo ni uso real.
 */
class Subsidiary extends BaseModel
{
    protected $appends = ['status_badge'];

    protected $casts = [
        'id' => 'string',
        'commerce_id' => 'string',
        'user_id' => 'string',
        'country_id' => 'string',
        'currency_id' => 'string',
        'commission' => 'decimal:2',
        'status' => 'string',
    ];

    protected $fillable = [
        'name',
        'commission',
        'phone',
        'commerce_id',
        'user_id',
        'country_id',
        'currency_id',
        'status',
    ];
    protected $table = 'subsidiaries';

    public static function getValueByUserId(string|int $userId, string $field) : mixed
    {
        return static::where('user_id', $userId)->value($field);
    }

    /**
     * Comercializadora a la que pertenece esta agencia. Nullable en BD por
     * decisión de esquema previa, pero en la práctica toda agencia real
     * debería tener commerce_id — una Subsidiary sin Commerce no encaja en
     * la jerarquía de negocio.
     */
    public function commerce() : BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
    }

    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getStatusBadgeAttribute() : string
    {
        return match ($this->status) {
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'BLOCKED' => '<span class="badge badge-danger">Bloqueado</span>',
            'DELETED' => '<span class="badge badge-dark">Eliminado</span>',
            default => '<span class="badge badge-warning">Desconocido</span>'
        };
    }

    /**
     * user_id de la propia agencia + de sus vendedores (un solo nivel: la
     * agencia no tiene sub-agencias). Equivalente a Commerce::networkUserIds()
     * un nivel más abajo en la jerarquía — mismo conjunto de vendedores que
     * SubsidiaryDashboardContextService::sellerUserIds().
     *
     * @return string[]
     */
    public function networkUserIds() : array
    {
        return $this->sellers()
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->push((string) $this->user_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Vendedores (taquillas) que reportan a esta agencia.
     * Inversa de Seller::subsidiary().
     */
    public function sellers() : HasMany
    {
        return $this->hasMany(Seller::class, 'subsidiary_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
