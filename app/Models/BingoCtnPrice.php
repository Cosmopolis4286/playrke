<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtnPrice extends Model
{
    protected $table = 'bingo_ctn_price';
    protected $fillable = ['room', 'creator_user', 'coin', 'ctn_price', 'status', 'created_at', 'updated_at'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
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

    public static function getCtnPriceByCreatorUser($coin, $creatorUser)
    {
        return self::where('creator_user', $creatorUser)->where('coin', $coin)->value('ctn_price');
    }

    public static function getOnlyCoinsCtnPrice($creatorUser)
    {
        if (!$creatorUser) return collect();

        return self::where('creator_user', $creatorUser)->groupBy('coin')
            ->select('coin', BingoCtnPrice::raw('MAX(ctn_price) as ctn_price'))
            ->pluck('ctn_price', 'coin');
    }

    public static function getAllCtnPrice($creatorUser)
    {
        return self::with('Coin')->where('creator_user', $creatorUser)->get();
    }

    public static function getCtnPrice($room, $coin, $creatorUser, $fields = ['id', 'ctn_price', 'status'])
    {
        return self::select($fields)->where('room', $room)->where('coin', $coin)
            ->where('creator_user', $creatorUser)->first();
    }
}
