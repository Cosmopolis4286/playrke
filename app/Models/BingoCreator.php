<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCreator extends Model
{
    protected $table = 'bingo_creator';
    protected $fillable = ['room', 'creator_type', 'creator_id', 'creator_commn', 'coin', 'ctn_price', 'created_at', 'updated_at'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room','id');
    }

    public function CreatorType()
    {
        return $this->belongsTo(UserType::class, 'creator_type');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'iso_code', 'coin');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByRoom($room, $fields = ['*'])
    {
        return self::select($fields)->where('room', $room)->first();
    }

    public static function getValueByRoom($room, $value)
    {
        return self::where('room', $room)->value($value);
    }

    public static function getByCreatorRoom($creator, $room, $fields = ['*'])
    {
        return self::select($fields)->where($creator['type'], $creator['id'])->where('room', $room)->first();
    }

    public static function getValueByCreatorRoom($creator, $room, $value)
    {
        return self::where($creator['type'], $creator['id'])->where('room', $room)->value($value);
    }
}
