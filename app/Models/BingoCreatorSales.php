<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCreatorSales extends Model
{
    protected $table = 'bingo_creator';
    protected $fillable = ['room', 'creator_user', 'coin', 'total_sale', 'created_at', 'updated_at'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function CreatorUser()
    {
        return $this->belongsTo(User::class, 'creator_user');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByRoomCreatorUser($room, $creatorUser, $coin, $fields = ['*'])
    {
        return self::select($fields)->where('room', $room)->where('coin', $coin)
            ->where('creator_user', $creatorUser)->first();
    }

    public static function existsByRoomCreatorUser($room, $creatorUser, $coin = '')
    {
        $query = self::where('room', $room)->where('creator_user', $creatorUser);
        if ($coin)  $query->where('coin', $coin);
        return $query->exists();
    }

    public static function updateOrCreateSale($roomID, $creatorUser, $coin, $amount)
    {
        $sale = self::firstOrCreate(
            ['room' => $roomID, 'creator_user' => $creatorUser, 'coin' => $coin],
            ['total_sale' => 0]
        );

        $sale->increment('total_sale', $amount);
    }
}
