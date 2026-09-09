<?php

namespace App\Models\Roles;

use App\Models\BaseModel;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Pivots\Roles\Commerce\CommerceCurrency;
use App\Models\Pivots\Roles\Commerce\CommerceGame;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para comercios
 */
class Commerce extends BaseModel
{
    protected $table = 'commerces';

    protected $fillable = [
        'name',
        'country_id',
        'phone',
        'user_id',
        'commission',
        'unique_code_id',
        'status'
    ];

    protected $casts = [
        'id' => 'string',
        'country_id' => 'string',
        'user_id' => 'string',
        'unique_code_id' => 'string',
        'commission' => 'decimal:2',
        'status' => 'string',
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
     * Relación con el código único
     */
    public function uniqueCode(): BelongsTo
    {
        return $this->belongsTo(UniqueCode::class, 'unique_code_id');
    }

    /**
     * Relación con los vendedores.
     *
     * La FK real vive en el lado de Seller (sellers.manager_user_id → commerces.user_id,
     * ver Seller::managerCommerce()); no existe columna commerce_id en sellers.
     */
    public function sellers(): HasMany
    {
        return $this->hasMany(Seller::class, 'manager_user_id', 'user_id');
    }

    /**
     * Agencias/sucursales de este comercio (jerarquía real: Comercializadora
     * → Agencia → Taquilla). FK directa commerce_id, a diferencia de
     * sellers() que resuelve vía user_id/manager_user_id.
     */
    public function subsidiaries(): HasMany
    {
        return $this->hasMany(Subsidiary::class, 'commerce_id');
    }

    /**
     * user_id del propio commerce + de TODA su red de vendedores: directos
     * (sellers() vía manager_user_id) y los que reportan a través de cada
     * Subsidiary (jerarquía Comercializadora → Agencia → Taquilla).
     *
     * Existía un hueco real antes de este método: CommerceDashboardContextService
     * y BingoReportController solo filtraban por el user_id del commerce
     * (creator_user_id de sala/lote propio), así que un commerce con
     * vendedores activos veía un total de ventas incompleto — nunca
     * contaba lo generado por su red. Mismo criterio de "vendedores
     * propios" que ya usa SubsidiaryDashboardContextService::sellerUserIds(),
     * extendido un nivel más arriba en la jerarquía.
     *
     * @return string[]
     */
    public function networkUserIds(): array
    {
        $directSellerIds = $this->sellers()->pluck('user_id')->map(fn ($id) => (string) $id);

        $subsidiarySellerIds = Seller::whereIn('subsidiary_id', $this->subsidiaries()->pluck('id'))
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id);

        return $directSellerIds
            ->merge($subsidiarySellerIds)
            ->push((string) $this->user_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Monedas que maneja el comercio
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Currency::class,
            'commerces_currencies',
            'commerce_id',
            'currency_id'
        )
            ->using(CommerceCurrency::class)
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
            'commerces_games',
            'commerce_id',
            'game_id'
        )
            ->using(CommerceGame::class)
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
}
