<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoPrizeCtns extends Model
{
    protected $table = "bingo_prize_ctns";
    protected $fillable = [
        'id',
        'active',
        'room',
        'creator_user',
        'seller',
        'room_user',
        'player_user',
        'player_link',
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

    public function Customer()
    {
        return $this->belongsTo(Customer::class, 'customer');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public function PlayerLink()
    {
        return $this->belongsTo(PlayerLink::class, 'player_link', 'id');
    }

    public function PlayerGame()
    {
        return $this->belongsTo(BingoRoomUser::class, 'player_link', 'player_link');
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

    public static function existsPrizeCtns($room, $prizeType)
    {
        $prizeType = $prizeType === 'JACKPOT' ? 1 : 2;

        return self::where(['room' => $room, 'prize_type' => $prizeType])->exists();
    }

    public static function existsPrizeCtn($room, $prizeType, $column, $value)
    {
        return self::where(['room' => $room, 'prize_type' => $prizeType, $column => $value])->exists();
    }

    public static function getValueById($ctnId, $value)
    {
        return self::where('ctn', $ctnId)->value($value);
    }

    public static function getWinnersIDsByPrizeType($room, $prizeType, $column, $active = 'Y')
    {
        return self::where('room', $room)->where('active', $active)
            ->where('prize_type', $prizeType)->pluck($column);
    }

    public static function getWinnersCountByPrizeType($room, $prizeType)
    {
        return self::where(['room' => $room, 'prize_type' => $prizeType])->count();
    }

    public static function getPendingPaymentsCountByCommerce($commerce)
    {
        return self::where('commerce', $commerce)->where('active', 'Y')
            ->where('player_link', '<>', '')->where('status', '<', 2)->count();
    }

    public static function getPendingPaymentsCountExcludingCommerce($commerce)
    {
        return self::whereNot('commerce', $commerce)->where('active', 'Y')
            ->where('player_link', '<>', '')->where('status', '<>', 3)->count();
    }
}
