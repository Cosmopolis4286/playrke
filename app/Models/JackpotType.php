<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JackpotType extends Model
{
    protected $table = "jackpot_types";
    protected $fillable = ['description'];

    public static function getByIds($arr)
    {
        return self::whereIn('id', $arr)->get();
    }
}
