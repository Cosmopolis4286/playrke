<?php

namespace App\Models\Roles\Player\External;

use App\Models\Currency;
use App\Models\Country;
use App\Models\Game;
use App\Models\Roles\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;

class ExternalPlayer extends BaseModel
{
    protected $fillable = [
        'game_id',
        'room_id',
        'creator_user_id',
        'seller_user_id',
        'room_user_id',
        'link',
        'expiration_date',
        'country_id',
        'currency_id',
        'first_name',
        'last_name',
        'identification',
        'phone',
        'purchase_code_id',
        'status',
        'converted_user_id',
    ];

    protected $casts = [
        'id' => 'string',
        'game_id' => 'string',
        'currency_id' => 'string',
        'purchase_code_id' => 'string',
        'room_id' => 'string',
        'creator_user_id' => 'string',
        'seller_user_id' => 'string',
        'country_id' => 'string',
        'room_user_id' => 'string',
        'status' => 'string',
        'expiration_date' => 'datetime',
        'converted_user_id' => 'string',
    ];

    /**
     * commerce/subsidiary son accessors calculados (ver getCommerceAttribute/
     * getSubsidiaryAttribute) — con $appends se serializan automáticamente
     * junto al resto de campos. Devuelven null si `seller` no está
     * precargado, así que es seguro en cualquier contexto de serialización
     * (no dispara N+1 queries).
     */
    protected $appends = ['commerce', 'subsidiary'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function sellerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    /**
     * Registro Seller (taquilla) del vendedor que captó a este jugador —
     * distinto de sellerUser() (el User asociado): este apunta al modelo de
     * dominio con la jerarquía real (subsidiary_id/manager_user_id), usado
     * para resolver comercio/agencia (ver Seller::resolveCommerce()).
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_user_id', 'user_id');
    }

    /**
     * Comercializadora del vendedor que captó a este jugador (directa o vía
     * agencia) — accessor calculado, no columna real. Requiere `seller` (y
     * sus relaciones subsidiary.commerce/managerCommerce) precargado por
     * ExternalPlayerAdminService::EAGER_RELATIONS; sin eso devuelve null en
     * vez de disparar una query N+1 por fila.
     */
    public function getCommerceAttribute(): ?array
    {
        if (!$this->relationLoaded('seller') || !$this->seller) {
            return null;
        }

        $commerce = $this->seller->subsidiary?->commerce ?? $this->seller->managerCommerce;

        return $commerce ? ['id' => (string) $commerce->id, 'name' => $commerce->name] : null;
    }

    /**
     * Agencia/sucursal del vendedor que captó a este jugador — null si
     * reporta directo al comercio sin agencia intermedia. Mismo criterio de
     * precarga que getCommerceAttribute().
     */
    public function getSubsidiaryAttribute(): ?array
    {
        if (!$this->relationLoaded('seller') || !$this->seller?->subsidiary) {
            return null;
        }

        return ['id' => (string) $this->seller->subsidiary->id, 'name' => $this->seller->subsidiary->name];
    }

    public function purchaseCode(): BelongsTo
    {
        return $this->belongsTo(UserPurchaseCode::class, 'purchase_code_id');
    }

    /**
     * Usuario registrado resultante de convertir este jugador externo (ver
     * ExternalPlayerAdminService::convertToUser()) — null mientras siga
     * siendo un comprador sin cuenta.
     */
    public function convertedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }

    /**
     * Obtener un valor específico de un jugador externo por su ID
     *
     * @param int|string $playerId ID del jugador
     * @param string $field Campo a obtener
     * @return mixed
     */
    public static function getValueById($playerId, string $field)
    {
        return static::where('id', $playerId)->value($field);
    }
}
