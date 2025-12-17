<?php

namespace App\Models\Users\Roles\Commerce;

use App\Models\Countries\Country;
use App\Models\Currencies\Currency;
use App\Models\Users\Roles\Seller\Seller;
use App\Models\Users\Shared\UniqueCode;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para comercios
 */
class Commerce extends Model
{
    use SnowflakeId;

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
        'commission' => 'decimal:2'
    ];

    /**
     * Accessor para el label de status con badge HTML
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-warning">Inactivo</span>',
            'BLOCKED' => '<span class="badge badge-danger">Bloqueado</span>',
            default => '<span class="badge badge-secondary">Desconocido</span>'
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
}
