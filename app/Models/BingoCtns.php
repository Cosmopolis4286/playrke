<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtns extends Model
{
    protected $table = 'bingo_ctns';
    protected $fillable = [
        'room',
        'customer',
        'commerce',
        'subsidiary',
        'seller',
        'player_user',
        'player_link',
        'coin',
        'lot',
        'serial',
        'created_at',
        'b1',
        'b2',
        'b3',
        'b4',
        'b5',
        'i1',
        'i2',
        'i3',
        'i4',
        'i5',
        'n1',
        'n2',
        'n3',
        'n4',
        'g1',
        'g2',
        'g3',
        'g4',
        'g5',
        'o1',
        'o2',
        'o3',
        'o4',
        'o5',
        'created_at'
    ];

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function PlayerLink()
    {
        return $this->belongsTo(PlayerLink::class, 'player_link', 'id');
    }

    public function Lot()
    {
        return $this->belongsTo(BingoCtnsLot::class, 'lot');
    }

    static function getLastCtnCreated($room)
    {
        return self::where('room', $room)->latest()->first();
    }

    static function getById($id,  $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    static function getCtnsByPlayerInRoom($room, $PJLink)
    {
        return self::where('room', $room)->where('player_link', $PJLink)
            ->get(['id', 'b1', 'b2', 'b3', 'b4', 'b5', 'i1', 'i2', 'i3', 'i4', 'i5', 'n1', 'n2', 'n3', 'n4', 'g1', 'g2', 'g3', 'g4', 'g5', 'o1', 'o2', 'o3', 'o4', 'o5']);
    }

    static function getCtnSerialByPlayerInRoom($room, $PJLink)
    {
        return self::where('room', $room)->where('player_link', $PJLink)->value('serial');
    }

    static function getCountCtnByRoom($room)
    {
        return self::where('room', $room)->count();
    }
}
