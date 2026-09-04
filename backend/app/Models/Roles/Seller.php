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
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'subsidiary_id',   // Agencia/sucursal a la que pertenece (opcional)
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
        'subsidiary_id' => 'string',
    ];

    // ========================
    // Accessors computados
    // ========================
    protected $appends = ['status_badge'];

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
     * Agencia/sucursal a la que pertenece este vendedor (jerarquía real:
     * Comercializadora → Agencia → Taquilla). Null si reporta directo al
     * Commerce sin agencia intermedia.
     */
    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(Subsidiary::class, 'subsidiary_id');
    }

    /**
     * Commerce que actúa como manager DIRECTO de este vendedor (caso sin
     * agencia intermedia). Busca el commerce cuyo user_id = manager_user_id.
     * Inversa de Commerce::sellers().
     *
     * OJO: cuando el seller tiene subsidiary_id, manager_user_id apunta al
     * usuario de la Subsidiary (rol 'subsidiary'), no a un Commerce — esta
     * relación devolvería null en ese caso. Usar resolveCommerce() para
     * obtener el Commerce real sin importar si hay agencia intermedia.
     */
    public function managerCommerce(): HasOne
    {
        return $this->hasOne(Commerce::class, 'user_id', 'manager_user_id');
    }

    /**
     * Resuelve el Commerce real de este vendedor sin importar si reporta
     * directo (manager_user_id → Commerce) o a través de una agencia
     * (subsidiary_id → Subsidiary → commerce_id → Commerce). Punto único de
     * verdad reemplazando las resoluciones ad-hoc que existían en
     * BingoCardGenerateService/SellerController/ManagerSellerContextService
     * (todas asumían manager_user_id → Commerce directo, rompiéndose
     * silenciosamente para sellers de una Subsidiary).
     */
    public function resolveCommerce(): ?Commerce
    {
        if ($this->subsidiary_id) {
            return $this->subsidiary?->commerce;
        }

        return $this->managerCommerce;
    }

    /**
     * true si este Seller y toda su cadena de jerarquía (Subsidiary si
     * aplica, y el Commerce resuelto por resolveCommerce()) están ACTIVE.
     *
     * Antes bloquear/desactivar un Commerce o Subsidiary no impedía que sus
     * Sellers subordinados (con status ACTIVE propio) siguieran generando
     * ventas con normalidad — BingoCardGenerateService solo validaba el
     * status de la BingoRoom, nunca el de la cadena comercial. Este método
     * centraliza esa validación (mismo criterio que resolveCommerce()
     * centraliza la resolución) para que el punto de venta la use antes de
     * generar cartones.
     */
    public function hasActiveHierarchy(): bool
    {
        if ($this->status !== 'ACTIVE') {
            return false;
        }

        if ($this->subsidiary_id && $this->subsidiary?->status !== 'ACTIVE') {
            return false;
        }

        $commerce = $this->resolveCommerce();

        return $commerce !== null && $commerce->status === 'ACTIVE';
    }

    /**
     * Accessor: nombre del manager directo (agencia si existe, si no el
     * commerce).
     */
    public function getManagerNameAttribute(): string
    {
        if ($this->subsidiary_id) {
            return $this->subsidiary?->name ?? '—';
        }

        return $this->managerCommerce?->name ?? '—';
    }

    /**
     * Accessor: rol del manager directo como badge HTML — antes hardcodeado
     * a "Commerce" incluso cuando el manager real era una Subsidiary.
     */
    public function getManagerRoleBadgeAttribute(): string
    {
        return $this->subsidiary_id
            ? '<span class="badge badge-teal text-white">Sucursal</span>'
            : '<span class="badge badge-info text-white">Commerce</span>';
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

    /**
     * Obtener el valor de un campo específico por user_id.
     */
    public static function getValueByUserId(string|int $userId, string $field): mixed
    {
        return static::where('user_id', $userId)->value($field);
    }

    /**
     * Obtener el valor de un campo específico por ID.
     */
    public static function getValueById(string|int $id, string $field): mixed
    {
        return static::where('id', $id)->value($field);
    }
}
