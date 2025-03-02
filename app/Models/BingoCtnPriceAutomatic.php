<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtnPriceAutomatic extends Model
{
    protected $table = 'automatic_bingo_ctn_price';
    protected $fillable = ['automatic_bingo_id', 'coin', 'ctn_price', 'status', 'created_at', 'updated_at'];

    public function AutomaticBingoRoom()
    {
        return $this->belongsTo(BingoRoomAutomatic::class, 'automatic_bingo_id');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getValueCtnPrice($coin, $automaticBingoID)
    {
        return self::where('automatic_bingo_id', $automaticBingoID)->where('coin', $coin)->value('ctn_price');
    }

    public static function getOnlyCoinsCtnPrice($automaticBingoID)
    {
        if (!$automaticBingoID) return collect();

        return self::where('automatic_bingo_id', $automaticBingoID)->groupBy('coin')
            ->select('coin', BingoCtnPrice::raw('MAX(ctn_price) as ctn_price'))
            ->pluck('ctn_price', 'coin');
    }

    public static function getAllCtnPrice($automaticBingoID)
    {
        return self::with('Coin')->where('automatic_bingo_id', $automaticBingoID)->get();
    }

    public static function getCtnPrice($coin, $automaticBingoID, $fields = ['id', 'ctn_price', 'status'])
    {
        return self::select($fields)->where('coin', $coin)->where('automatic_bingo_id', $automaticBingoID)->first();
    }
}
