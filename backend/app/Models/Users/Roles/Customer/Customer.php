<?php

namespace App\Models\Users\Roles\Customer;

use App\Models\Countries\Country;
use App\Models\Currencies\Currency;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo para clientes (Customer).
 *
 * Representa a los clientes que se conectan al sistema mediante API.
 * Cada cliente tiene credenciales únicas y puede manejar múltiples monedas y juegos.
 *
 * @property string $id
 * @property string $name Nombre del cliente
 * @property string $customer_id ID único del cliente
 * @property string $country_id Foreign key a countries
 * @property string $currency_id Foreign key a currencies (moneda principal)
 * @property string $user_id Foreign key a users
 * @property string $phone Teléfono de contacto
 * @property string $api_route Ruta API del cliente
 * @property string $customer_key Llave de conexión única
 * @property string|null $logo Ruta del logo
 * @property string $status Estado del cliente (ACTIVE, INACTIVE, BLOCKED, DELETED)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Customer extends Model
{
    use SnowflakeId;

    /**
     * Nombre de la tabla en la base de datos.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'customer_id',
        'country_id',
        'currency_id',
        'user_id',
        'phone',
        'api_route',
        'customer_key',
        'logo',
        'status'
    ];

    /**
     * Casting de atributos a tipos específicos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'country_id' => 'string',
        'currency_id' => 'string',
        'user_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Accessor para el label de status con badge HTML.
     *
     * Genera un badge Bootstrap según el estado actual del cliente.
     *
     * @return string HTML del badge
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-warning">Inactivo</span>',
            'BLOCKED' => '<span class="badge badge-danger">Bloqueado</span>',
            'DELETED' => '<span class="badge badge-secondary">Eliminado</span>',
            default => '<span class="badge badge-secondary">Desconocido</span>'
        };
    }

    /**
     * Relación con el país.
     *
     * Un cliente pertenece a un país.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relación con la moneda principal.
     *
     * Un cliente tiene una moneda principal para sus operaciones.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relación con el usuario.
     *
     * Un cliente está asociado a un usuario del sistema.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Monedas adicionales que maneja el cliente.
     *
     * Relación many-to-many con currencies a través de la tabla pivot
     * customers_currencies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function otherCurrencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Currency::class,
            'customers_currencies',
            'customer_id',
            'currency_id'
        )->withTimestamps();
    }

    /**
     * Juegos permitidos para el cliente.
     *
     * Relación many-to-many con games a través de la tabla pivot
     * customers_games.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function allowedGames(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Games\Shared\Game::class,
            'customers_games',
            'customer_id',
            'game_id'
        )->withTimestamps();
    }
}
