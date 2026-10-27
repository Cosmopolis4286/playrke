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
    // ========================
    // Accessors computados
    // ========================
    protected $appends = ['status_badge'];

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
     * El token del enlace público de venta funciona como credencial de
     * entrada a /comprar/{token}: no debe viajar en serializaciones del
     * modelo (listados admin, recursos API). Se lee explícitamente vía
     * SellerSalesLinkService.
     *
     * @var array<int, string>
     */
    protected $hidden = ['sales_link_token'];

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sellers';

    /**
     * Obtener el valor de un campo específico por ID.
     */
    public static function getValueById(string|int $id, string $field) : mixed
    {
        return static::where('id', $id)->value($field);
    }

    /**
     * Obtener el valor de un campo específico por user_id.
     */
    public static function getValueByUserId(string|int $userId, string $field) : mixed
    {
        return static::where('user_id', $userId)->value($field);
    }

    /**
     * Relación con el país
     */
    public function country() : BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Monedas que maneja el comercio
     */
    public function currencies() : BelongsToMany
    {
        return $this->belongsToMany(
            Currency::class,
            'sellers_currencies',
            'seller_id',
            'currency_id',
        )
            ->using(SellerCurrency::class)
            ->withPivot(['level', 'status'])
            ->withTimestamps();
    }

    /**
     * Monedas activas que maneja el comercio
     */
    public function currenciesActive() : BelongsToMany
    {
        return $this->currencies()
            ->wherePivot('status', 'ACTIVE');
    }

    /**
     * Juegos permitidos para el comercio
     */
    public function games() : BelongsToMany
    {
        return $this->belongsToMany(
            Game::class,
            'sellers_games',
            'seller_id',
            'game_id',
        )
            ->using(SellerGame::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Juegos permitidos activos para el comercio
     */
    public function gamesActive() : BelongsToMany
    {
        return $this->games()
            ->wherePivot('status', 'ACTIVE');
    }

    /**
     * Accessor: nombre del manager directo (agencia si existe, si no el
     * commerce).
     */
    public function getManagerNameAttribute() : string
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
    public function getManagerRoleBadgeAttribute() : string
    {
        if ($this->subsidiary_id) {
            return '<span class="badge badge-teal text-white">Sucursal</span>';
        }

        // Huérfano: manager_user_id no es una Comercializadora (vendedores
        // legados creados por un 'customer'). No puede vender
        // (hasActiveHierarchy() false) hasta que admin lo reasigne — ver
        // commissions:audit-hierarchy.
        return $this->managerCommerce
            ? '<span class="badge badge-info text-white">Commerce</span>'
            : '<span class="badge badge-danger text-white">Sin comercializadora</span>';
    }

    /**
     * Accessor para el status con badge HTML
     */
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
    public function hasActiveHierarchy() : bool
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
     * Commerce cuyo user_id = manager_user_id. Inversa de Commerce::sellers().
     *
     * manager_user_id siempre se persiste con el user_id del Commerce raíz,
     * también para sellers de agencia (ver ManagerSellerService::
     * resolveManagerForUser(), y AdminSubsidiaryCrudService::update(), que
     * lo resincroniza si la agencia cambia de Commerce). Aun así, la fuente
     * de verdad de la jerarquía es resolveCommerce() (subsidiary_id →
     * Subsidiary → Commerce cuando hay agencia) — usar esa, no esta relación.
     */
    public function managerCommerce() : HasOne
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
    public function resolveCommerce() : ?Commerce
    {
        if ($this->subsidiary_id) {
            return $this->subsidiary?->commerce;
        }

        return $this->managerCommerce;
    }

    /**
     * user_ids de los dueños de salas (PRIVATE incluidas) en las que este
     * vendedor puede vender: su comercializadora y, si es de agencia, también
     * su agencia — las agencias no crean salas por defecto, pero una con
     * permiso individual games.bingo.manage sí, y sus vendedores deben poder
     * venderlas. Para BingoRoom::scopeAccessibleBy().
     *
     * @return string[]
     */
    public function roomOwnerUserIds() : array
    {
        return array_values(array_filter([
            $this->resolveCommerce()?->user_id ? (string) $this->resolveCommerce()->user_id : null,
            $this->subsidiary_id && $this->subsidiary?->user_id ? (string) $this->subsidiary->user_id : null,
        ]));
    }

    /**
     * Agencia/sucursal a la que pertenece este vendedor (jerarquía real:
     * Comercializadora → Agencia → Taquilla). Null si reporta directo al
     * Commerce sin agencia intermedia.
     */
    public function subsidiary() : BelongsTo
    {
        return $this->belongsTo(Subsidiary::class, 'subsidiary_id');
    }

    /**
     * Relación con el usuario
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
