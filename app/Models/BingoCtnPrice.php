<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtnPrice extends Model
{
    protected $table = 'bingo_ctn_price';
    protected $fillable = ['room', 'creator_type', 'creator_id', 'bingo_creator_id', 'coin', 'ctn_price', 'status', 'created_at', 'updated_at'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function CreatorType()
    {
        return $this->belongsTo(UserType::class, 'creator_type');
    }

    public function BingoCreator()
    {
        return $this->belongsTo(BingoCreator::class, 'bingo_creator_id');
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

    public static function getOnlyCoinsCtnPrice($bingoCreatorID)
    {
        return self::where('bingo_creator_id', $bingoCreatorID)->groupBy('coin')
            ->select('coin', BingoCtnPrice::raw('MAX(ctn_price) as ctn_price'))
            ->pluck('ctn_price', 'coin');
    }

    public static function getAllCtnPrice($bingoCreatorID)
    {
        return self::with('Coin')->where('bingo_creator_id', $bingoCreatorID)->get();
    }

    public static function getCtnPrice($room, $coin, $bingoCreatorID, $fields = ['id', 'ctn_price', 'status'])
    {
        return self::select($fields)->where('room', $room)->where('coin', $coin)
            ->where('bingo_creator_id', $bingoCreatorID)->first();
    }
}
