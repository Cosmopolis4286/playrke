<?php

namespace App\Models\Users\Roles\Commerce;

use App\Models\Concerns\HasHashedRouteKey;
use App\Models\Countries\Country;
use App\Models\Currencies\Currency;
use App\Models\Users\Roles\Seller\Seller;
use App\Models\Users\Shared\UniqueCode;
use App\Models\Users\User;
use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para comercios
 */
class Commerce extends Model
{
    use SnowflakeId;
    use HasHashedRouteKey;
    use SoftDeletes;
    use Auditable;

    protected $table = 'commerces';

    protected $fillable = [
        'name',
        'country_id',
        'currency_id',
        'phone',
        'user_id',
        'commission',
        'unique_code_id',
        'status'
    ];

    protected $casts = [
        'id' => 'string',
        'country_id' => 'string',
        'currency_id' => 'string',
        'user_id' => 'string',
        'unique_code_id' => 'string',
        'commission' => 'decimal:2'
    ];

    /**
     * Accessor para el label de status con badge HTML
     */
    public function getStatusLabelAttribute(): string
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
     * Relación con el país
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relación con la moneda
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el código único
     */
    public function uniqueCode(): BelongsTo
    {
        return $this->belongsTo(UniqueCode::class, 'unique_code_id');
    }

    /**
     * Relación con los vendedores
     */
    public function sellers(): HasMany
    {
        return $this->hasMany(Seller::class, 'commerce_id');
    }

    /**
     * Monedas adicionales que maneja el comercio
     */
    public function otherCurrencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Currency::class,
            'commerces_countries_currencies',
            'commerce_id',
            'currency_id'
        )->withTimestamps();
    }

    /**
     * Juegos permitidos para el comercio
     */
    public function allowedGames(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Games\Shared\Game::class,
            'commerces_games',
            'commerce_id',
            'game_id'
        )->withTimestamps();
    }
}
