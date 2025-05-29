<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypePrizePool extends Model
{
    protected $table = "prize_pool_types";
    protected $fillable = ['description'];

    public static function getByIds($arr)
    {
        return self::whereIn('id', $arr)->get();
    }
}
