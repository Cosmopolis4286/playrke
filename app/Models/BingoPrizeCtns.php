<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoPrizeCtns extends Model
{
    protected $table = "bingo_prize_ctns";
    protected $fillable = [
        'active',
        'room',
        'round',
        'creator_user',
        'seller',
        'room_user',
        'player_user',
        'lot',
        'ctn',
        'prize_type',
        'usd_amount',
        'coin',
        'amount',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $appends = ['prize_type_description'];

    public const PRIZE_TYPES = [
        'FLHE' => 'Cartón Lleno',
        'SONG' => 'Canto',
        'SRL' => 'Serial'
    ];

    public function getPrizeTypeDescriptionAttribute()
    {
        return self::PRIZE_TYPES[$this->prize_type] ?? '';
    }

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'seller');
    }

    public function PlayerLink()
    {
        return $this->belongsTo(PlayerLink::class, 'room_user', 'room_user');
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

    public function Payments()
    {
        return $this->hasMany(BingoPrizeCtnsPayment::class, 'prize_ctn', 'id');
    }

    public static function getById($ctnId, $fields = ['*'])
    {
        return self::select($fields)->where('ctn', $ctnId)->first();
    }

    public static function existsPrizeCtns($room, $prizeType)
    {
        $prizeType = GamePrizes::getValueByDescription('BINGO', $prizeType);

        return self::where(['room' => $room, 'prize_type' => $prizeType])->exists();
    }

    public static function existsPrizeCtn($ctnId, $prizeType = null)
    {
        return self::where('ctn', $ctnId)
            ->when($prizeType, fn($query) => $query->where('prize_type', $prizeType))
            ->exists();
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
        $prizeType = GamePrizes::getValueByDescription('BINGO', $prizeType);
        return self::where(['room' => $room, 'prize_type' => $prizeType])->count();
    }

    public static function getPendingPaymentsCountByCreator($creatorUser)
    {
        return self::where('creator_user', $creatorUser)->where('active', 'Y')
            ->where('room_user', '<>', '')->where('status', '<', 2)->count();
    }

    public static function getPendingPaymentsCountExcludingCreator($creatorUser)
    {
        return self::whereNot('creator_user', $creatorUser)->where('active', 'Y')
            ->where('room_user', '<>', '')->where('status', '<>', 3)->count();
    }
}
