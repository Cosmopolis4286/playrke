<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtnPrice extends Model
{
    protected $table = 'bingo_ctn_price';
    protected $fillable = ['room', 'creator_type', 'creator_id', 'coin', 'ctn_price', 'status', 'created_at', 'updated_at'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function CreatorType()
    {
        return $this->belongsTo(UserType::class, 'creator_type');
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

    public static function getOnlyCoinsCtnPrice($creatorType, $creatorID)
    {
        // Convertir $creatorType si es un string
        if (is_string($creatorType)) {
            $creatorType = UserType::getValueByPrefix($creatorType, 'id');
        }

        return self::where([
            ['creator_type', $creatorType],
            ['creator_id', $creatorID],
        ])->groupBy('coin')->select('coin', BingoCtnPrice::raw('MAX(ctn_price) as ctn_price'))->pluck('ctn_price', 'coin');
    }

    public static function getAllCtnPrice($creatorType, $creatorID)
    {
        return self::with('Coin')->where('creator_type', $creatorType)
            ->where('creator_id', $creatorID)->get();
    }

    public static function getCtnPrice($room, $coin, $value = 'ctn_price')
    {
        return self::where('room', $room)->where('coin', $coin)->value($value);
    }
}
