<?php

namespace App\Models\Games\Bingo\Creators;

use Illuminate\Database\Eloquent\Model;

class BingoCreatorSales extends Model
{
    protected $table = 'bingo_creator_sales';
    protected $fillable = [
        'room',
        'creator_user_id',
        'currency',
        'sale_amount',
        'rke_commn',
        'room_commn',
        'seller_commn',
        'prize_pool_amount',
        'created_at',
        'updated_at'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function Currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public function CreatorUser()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByRoomCreatorUser($room, $creatorUser, $currency, $fields = ['*'])
    {
        return self::select($fields)->where('room', $room)->where('currency', $currency)
            ->where('creator_user_id', $creatorUser)->first();
    }

    public static function getValueByRoomCreatorUser($room, $creatorUser, $currency, $value)
    {
        return self::where('room', $room)->where('currency', $currency)
            ->where('creator_user_id', $creatorUser)->value($value);
    }

    public static function existsByRoomCreatorUser($room, $creatorUser, $currency = '')
    {
        $query = self::where('room', $room)->where('creator_user_id', $creatorUser);
        if ($currency)  $query->where('currency', $currency);
        return $query->exists();
    }

    public static function getPrizePoolAmountByRoomAndCurrency($room, $currency)
    {
        return self::where('room', $room)->where('currency', $currency)->sum('prize_pool_amount');
    }

    public static function updateOrCreateSale($roomId, $creatorUser, $currency, $amount, $sellerCommn)
    {
        $roomConfig = BingoRoomConfig::getByRoom($roomId, ['rke_commn', 'room_commn']);

        $rkeCommn =  round($amount * ($roomConfig->rke_commn / 100), 3);
        $roomCommn = round($amount * ($roomConfig->room_commn / 100), 3);
        $sellerCommn = round(($sellerCommn ?? 0), 3);
        $prizePool = round(($amount - $rkeCommn - $roomCommn - $sellerCommn), 3);

        $sale = self::firstOrCreate(
            ['room' => $roomId, 'creator_user_id' => $creatorUser, 'currency' => $currency],
            [
                'sale_amount' => $amount,
                'rke_commn' => $rkeCommn,
                'room_commn' => $roomCommn,
                'seller_commn' => $sellerCommn,
                'prize_pool_amount' => $prizePool
            ]
        );

        if (!$sale->wasRecentlyCreated && $amount) {
            $sale->increment('sale_amount', $amount);
            $sale->increment('rke_commn', $rkeCommn);
            $sale->increment('room_commn', $roomCommn);
            $sale->increment('seller_commn', $sellerCommn);
            $sale->increment('prize_pool_amount', $prizePool);
        }
    }
}
