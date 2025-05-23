<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    protected $table = "user_type";
    protected $fillable = ['id', 'description', 'prefix', 'status'];

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getValueByPrefix($prefix, $value = 'id')
    {
        return self::where('prefix', $prefix)->value($value);
    }
}
