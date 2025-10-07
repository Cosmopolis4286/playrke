<?php

namespace App\Models\Users\Roles\Commerce;

use App\Models\Countries\Country;
use App\Models\Currencies\Currency;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para países y monedas de comercios
 */
class CommerceCountryCurrency extends Model
{
    use SnowflakeId;

    protected $table = 'commerces_countries_currencies';

    protected $fillable = [
        'commerce_id',
        'country_id',
        'currency_id',
        'level',
        'creator_user_id',
        'status'
    ];

    protected $casts = [
        'level' => 'integer'
    ];

    /**
     * Relación con el comercio
     */
    public function commerce(): BelongsTo
    {
        return $this->belongsTo(Commerce::class, 'commerce_id');
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
     * Relación con el usuario creador
     */
    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }
}