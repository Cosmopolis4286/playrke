<?php

namespace App\Models\Users\Roles\Player\External;

use Illuminate\Database\Eloquent\Model;

class UserPurchaseCodes extends Model
{
    protected $table = "user_purchase_codes";
    protected $fillable = ['name', 'lastname', 'country', 'phone', 'code', 'status'];

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function existsPurchaseCode($code)
    {
        return self::where('code', $code)->exists();
    }
}
