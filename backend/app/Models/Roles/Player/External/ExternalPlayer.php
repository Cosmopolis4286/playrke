<?php

namespace App\Models\Roles\Player\External;

use App\Models\Currency;
use App\Models\Country;
use App\Models\Game;
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
        'room_id' => 'string',
        'creator_user_id' => 'string',
        'seller_user_id' => 'string',
        'country_id' => 'string',
        'room_user_id' => 'string',
        'status' => 'string',
        'expiration_date' => 'datetime',
        'converted_user_id' => 'string',
    ];

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
