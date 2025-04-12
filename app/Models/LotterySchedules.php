<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotterySchedules extends Model
{
    protected $table = 'lotteries_schedules';
    protected $fillable = ['lottery_id', 'hour', 'status'];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getBylotteryId($lotteryId, $fields = ['*'])
    {
        return self::select($fields)->where('lottery_id', $lotteryId)->first();
    }

    public static function getValueByLotteryId($lotteryId, $value)
    {
        return self::where('lottery_id', $lotteryId)->value($value);
    }
}
