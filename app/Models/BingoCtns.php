<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCtns extends Model
{
    protected $table = 'bingo_ctns';
    protected $fillable = [
        'room',
        'creator_user',
        'seller',
        'room_user',
        'player_user',
        'coin',
        'lot',
        'serial',
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
        'hash',
        'created_at'
    ];

    public const ALL_BOXES = ['B1', 'B2', 'B3', 'B4', 'B5', 'I1', 'I2', 'I3', 'I4', 'I5', 'N1', 'N2', 'N4', 'G1', 'G2', 'G3', 'G4', 'G5', 'O1', 'O2', 'O3', 'O4', 'O5'];

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
        return $this->belongsTo(PlayerLink::class, 'room_user', 'room_user');
    }

    public function Lot()
    {
        return $this->belongsTo(BingoCtnsLot::class, 'lot', 'id');
    }

    static function getLastCtnCreatedByRoom($room)
    {
        return self::where('room', $room)->orderByDesc('id')->first();
    }

    static function getById($id,  $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    static function getCtnsByPlayerInRoom($room, $column, $value)
    {
        return self::where('room', $room)->where($column, $value)
            ->get(['id', 'serial', 'b1', 'b2', 'b3', 'b4', 'b5', 'i1', 'i2', 'i3', 'i4', 'i5', 'n1', 'n2', 'n3', 'n4', 'g1', 'g2', 'g3', 'g4', 'g5', 'o1', 'o2', 'o3', 'o4', 'o5']);
    }

    static function getCountCtnByRoom($room)
    {
        return self::where('room', $room)->count();
    }

    static function existsCartonHashRoom($room, $hash)
    {
        return self::where('room', $room)->where('hash', $hash)->exists();
    }
}
