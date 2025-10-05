<?php

namespace App\Models\Users\Roles\Player\External;

use App\Traits\HasSnowflakeId;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para códigos de compra de usuarios.
 */
class UserPurchaseCode extends Model
{
    use HasSnowflakeId;

    protected $table = "user_purchase_codes";

    protected $fillable = [
        'name',
        'lastname',
        'country',
        'phone',
        'code',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];
}

    public static function existsPurchaseCode($code)
    {
        return self::where('code', $code)->exists();
    }
}
