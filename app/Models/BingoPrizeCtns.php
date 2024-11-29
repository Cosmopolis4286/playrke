<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoPrizeCtns extends Model
{
    protected $table = "bingo_prize_ctns";
    protected $fillable = [
        'room',
        'creator_type',
        'creator_id',
        'seller',
        'room_user',
        'ctn',
        'prize_type',
        'coin',
        'amount',
        'status',
        'created_at',
        'updated_at'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public function PlayerGame()
    {
        return $this->belongsTo(BingoRoomUser::class, 'room_user');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public function GameExchangeRate()
    {
        return $this->hasMany(GameExchangeRate::class, 'room', 'room');
    }

    public function Ctns()
    {
        return $this->belongsTo(BingoCtns::class, 'ctn');
    }

    public function CtnsLot()
    {
        return $this->belongsTo(BingoCtnsLot::class, 'lot');
    }

    public function Payment()
    {
        return $this->hasMany(BingoPrizeCtnsPayment::class, 'prize_ctn', 'id');
    }

    public function Prize()
    {
        return $this->belongsTo(GamePrizes::class, 'prize_type');
    }

    public static function getInfoByCtn($ctnId, $fields = ['*'])
    {
        return self::select($fields)->where('ctn', $ctnId)->first();
    }

    public static function existsPrizeCtn($room, $prizeType, $roomUser)
    {
        return self::where('room', $room)->where('prize_type', $prizeType)
            ->where('room_user', $roomUser)->exists();
    }

    public static function getValueById($ctnId, $value)
    {
        return self::where('ctn', $ctnId)->value($value);
    }

    public static function getWinnersIDsByPrizeType($room, $prizeType, $column)
    {
        return self::where(['room' => $room, 'prize_type' => $prizeType])->pluck($column);
    }

    public static function getWinnersCountByPrizeType($room, $prizeType)
    {
        return self::where(['room' => $room, 'prize_type' => $prizeType])->count();
    }

    public static function getPendingPaymentsCountByCommerce($commerce)
    {
        return self::where('commerce', $commerce)
            ->where('player_link', '<>', '')->where('status', '<', 2)->count();
    }

    public static function getPendingPaymentsCountExcludingCommerce($commerce)
    {
        return self::whereNot('commerce', $commerce)
            ->where('player_link', '<>', '')->where('status', '<>', 3)->count();
    }
}
