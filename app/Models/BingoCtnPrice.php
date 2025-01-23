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

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getCtnPrice($room, $coin, $value = 'ctn_price')
    {
        return self::where('room', $room)->where('coin', $coin)->value($value);
    }
}
