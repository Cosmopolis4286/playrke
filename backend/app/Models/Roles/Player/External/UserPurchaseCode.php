<?php

namespace App\Models\Roles\Player\External;

use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para códigos de compra de usuarios.
 */
class UserPurchaseCode extends Model
{
    use SnowflakeId;

    protected $table = "user_purchase_codes";

    protected $fillable = [
        'first_name',
        'last_name',
        'country_id',
        'phone',
        'purchase_code',
        'status',
        'expiration_date'
    ];

    protected $casts = [
        'status' => 'string',
        'expiration_date' => 'datetime'
    ];


    public static function existsPurchaseCode($code)
    {
        return self::where('purchase_code', $code)->exists();
    }
}
