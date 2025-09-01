<?php

namespace App\Models\Games\Shared\PrizePool;

use Illuminate\Database\Eloquent\Model;

class PrizePoolType extends Model
{
    protected $table = "prize_pool_types";
    protected $fillable = ['description'];

    public static function getByIds($arr)
    {
        return self::whereIn('id', $arr)->get();
    }
}
