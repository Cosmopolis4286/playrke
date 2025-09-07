<?php

namespace App\Models\Games\Bingo\Cartons\CartonPrice;

use App\Models\Currencies\Currencys\Coin;
use Illuminate\Database\Eloquent\Model;

class BingoCtnPrice extends Model
{
    protected $table = 'bingo_ctn_price';
    protected $fillable = ['room', 'creator_user_id', 'currency_id', 'ctn_price', 'created_at', 'updated_at'];

    public function Coin()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getValueCtnPrice($room, $currencyId, $creatorUser)
    {
        return self::select('ctn_price')->where('room', $room)->where('currency_id', $currencyId)
            ->where('creator_user_id', $creatorUser)->value('ctn_price');
    }

    public static function getAllCoinsCtnPrice($room, $creatorUser)
    {
        if (!$creatorUser) return collect();

        return self::select('currency_id')->where('room', $room)
            ->where('creator_user_id', $creatorUser)->distinct()->pluck('currency_id');
    }

    public static function getOnlyCoinsCtnPrice($room, $creatorUser, string $creation)
    {
        if (!$creatorUser) return collect();

        return self::select('currency_id', BingoCtnPrice::raw('MAX(ctn_price) as ctn_price'))
            ->where('room', $room)->where('creator_user_id', $creatorUser)->groupBy('currency_id')
            ->pluck('ctn_price', 'currency_id');
    }

    public static function getAllCtnPrice($room, $creatorUser)
    {
        return self::with('Coin')->where('room', $room)->where('creator_user_id', $creatorUser)->get();
    }

    public static function getCtnPrice($room, $currencyId, $creatorUser, $fields = ['id', 'ctn_price'])
    {
        return self::select($fields)->where('room', $room)->where('currency_id', $currencyId)
            ->where('creator_user_id', $creatorUser)->first();
    }
}
