<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffPrizeCtns extends Model
{
    protected $table = "bingo_off_prize_ctns";
    protected $fillable = [
        'id',
        'room',
        'provider',
        'commerce',
        'seller',
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
        return $this->belongsTo(BingoOffRoom::class, 'room');
    }

    public function Provider()
    {
        return $this->belongsTo(Provider::class, 'provider');
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
        return $this->belongsTo(BingoOffRoomUser::class, 'player_link', 'player_link');
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
        return $this->belongsTo(BingoOffCtns::class, 'ctn');
    }

    public function CtnsLot()
    {
        return $this->belongsTo(BingoOffCtnsLot::class, 'lot');
    }

    public function Payment()
    {
        return $this->hasMany(BingoOffPrizeCtnsPayment::class, 'prize_ctn', 'id');
    }

    public function Prize()
    {
        return $this->belongsTo(GamePrizes::class, 'prize_type');
    }

    public static function getInfoByCtn($ctnId, $fields = ['*'])
    {
        return self::select($fields)->where('ctn', $ctnId)->first();
    }

    public static function existsPrizeCtn($room, $prizeType, $playerLink)
    {
        return self::where(['room' => $room, 'prize_type' => $prizeType, 'player_link' => $playerLink])->exists();
    }

    public static function getValueById($ctnId, $value)
    {
        return self::where('ctn', $ctnId)->value($value);
    }

    public static function getWinnersCountByPrizeType($room, $prizeType)
    {
        return self::where(['room' => $room, 'prize_type' => $prizeType])->count();
    }

    public static function getPendingPaymentsCountByCommerce($commerce)
    {
        return self::where('commerce', $commerce)->where('status', '<', 2)->count();
    }

    public static function getPendingPaymentsCountExcludingCommerce($commerce)
    {
        return self::whereNot('commerce', $commerce)->where('status', '<>', 3)->count();
    }
}
