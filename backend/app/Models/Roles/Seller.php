<?php

namespace App\Models\Roles;

use App\Models\BaseModel;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Pivots\Roles\Seller\SellerCurrency;
use App\Models\Pivots\Roles\Seller\SellerGame;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo para vendedores
 */
class Seller extends BaseModel
{

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sellers';

    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',           // ID del vendedor (generado con Snowflake)
        'name',         // Nombre del vendedor
        'phone',        // Teléfono del vendedor
        'commission',   // Comisión del vendedor
        'user_id',         // ID del usuario asociado
        'country_id',      // ID del país
        'manager_user_id', // ID del usuario manager al que representa
        'status',       // Estado del vendedor (activo/inactivo)
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'commission' => 'decimal:2',
        'user_id' => 'string',
        'country_id' => 'string',
        'manager_user_id' => 'string',
    ];

    /**
     * Accessor para el status con badge HTML
     */
    public function getStatusBadgeAttribute(): string
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
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Monedas que maneja el comercio
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Currency::class,
            'sellers_currencies',
            'seller_id',
            'currency_id'
        )
            ->using(SellerCurrency::class)
            ->withPivot(['level', 'status'])
            ->withTimestamps();
    }

    /**
     * Juegos permitidos para el comercio
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(
            Game::class,
            'sellers_games',
            'seller_id',
            'game_id'
        )
            ->using(SellerGame::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Monedas activas que maneja el comercio
     */
    public function currenciesActive(): BelongsToMany
    {
        return $this->currencies()
            ->wherePivot('status', 'ACTIVE');
    }

    /**
     * Juegos permitidos activos para el comercio
     */
    public function gamesActive(): BelongsToMany
    {
        return $this->games()
            ->wherePivot('status', 'ACTIVE');
    }
}
