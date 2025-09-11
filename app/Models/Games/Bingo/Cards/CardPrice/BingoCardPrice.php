<?php

namespace App\Models\Games\Bingo\Cartons\CartonPrice;

use App\Models\Currencies\Currency;
use Illuminate\Database\Eloquent\Model;

class BingoCardPrice extends Model
{
    protected $table = 'bingo_card_prices';
    protected $fillable = ['room', 'creator_user_id', 'currency_id', 'card_price', 'created_at', 'updated_at'];

    public function Currency()
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
        return self::select('card_price')->where('room', $room)->where('currency_id', $currencyId)
            ->where('creator_user_id', $creatorUser)->value('card_price');
    }

    public static function getAllCurrenciesCtnPrice($room, $creatorUser)
    {
        if (!$creatorUser) return collect();

        return self::select('currency_id')->where('room', $room)
            ->where('creator_user_id', $creatorUser)->distinct()->pluck('currency_id');
    }

    public static function getOnlyCurrenciesCtnPrice($room, $creatorUser, string $creation)
    {
        if (!$creatorUser) return collect();

        return self::select('currency_id', BingoCardPrice::raw('MAX(card_price) as card_price'))
            ->where('room', $room)->where('creator_user_id', $creatorUser)->groupBy('currency_id')
            ->pluck('card_price', 'currency_id');
    }

    public static function getAllCtnPrice($room, $creatorUser)
    {
        return self::with('Currency')->where('room', $room)->where('creator_user_id', $creatorUser)->get();
    }

    public static function getCtnPrice($room, $currencyId, $creatorUser, $fields = ['id', 'card_price'])
    {
        return self::select($fields)->where('room', $room)->where('currency_id', $currencyId)
            ->where('creator_user_id', $creatorUser)->first();
    }
}
