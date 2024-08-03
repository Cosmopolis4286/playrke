<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    protected $table = "user_type";
    protected $fillable = ['description', 'prefix', 'status'];

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }
}
