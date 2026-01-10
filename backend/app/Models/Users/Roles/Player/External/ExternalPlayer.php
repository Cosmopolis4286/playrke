<?php

namespace App\Models\Users\Roles\Player\External;

use App\Models\Currencies\Currency;
use App\Models\Countries\Country;
use App\Models\Games\Game;
use App\Models\Users\Roles\Player\External\UserPurchaseCode;
use App\Models\Users\Roles\Seller\Seller;
use App\Models\Users\User;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalPlayer extends Model
{
    use SnowflakeId;

    protected $fillable = [
        'game_id',
        'room',
        'creator_user_id',
        'seller_id',
        'room_user',
        'link',
        'country_id',
        'currency_id',
        'first_name',
        'last_name',
        'identification',
        'phone',
        'purchase_code_id',
        'status'
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

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function purchaseCode(): BelongsTo
    {
        return $this->belongsTo(UserPurchaseCode::class, 'purchase_code_id');
    }
}
