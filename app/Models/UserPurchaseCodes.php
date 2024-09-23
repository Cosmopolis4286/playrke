<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPurchaseCodes extends Model
{
    protected $table = "user_purchase_codes";
    protected $fillable = ['name', 'lastname', 'country', 'phone', 'code', 'status'];

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }
}
