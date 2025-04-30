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

    public static function getValueCtnPrice($room, $coin, $creatorUser)
    {
        return self::where('room', $room)->where('coin', $coin)
            ->where('creator_user', $creatorUser)->value('ctn_price');
    }

    public static function getAllCoinsCtnPrice($room, $creatorUser, $status = 1)
    {
        if (!$creatorUser) return collect();

        return self::where('room', $room)->where('creator_user', $creatorUser)
            ->where('status', $status)->pluck('coin');
    }

    public static function getOnlyCoinsCtnPrice($room, $creatorUser)
    {
        if (!$creatorUser) return collect();

        return self::where('room', $room)->where('creator_user', $creatorUser)->groupBy('coin')
            ->select('coin', BingoCtnPrice::raw('MAX(ctn_price) as ctn_price'))
            ->pluck('ctn_price', 'coin');
    }

    public static function getAllCtnPrice($room, $creatorUser)
    {
        return self::with('Coin')->where('room', $room)->where('creator_user', $creatorUser)->get();
    }

    public static function getCtnPrice($room, $coin, $creatorUser, $fields = ['id', 'ctn_price', 'status'])
    {
        return self::select($fields)->where('room', $room)->where('coin', $coin)
            ->where('creator_user', $creatorUser)->first();
    }
}
