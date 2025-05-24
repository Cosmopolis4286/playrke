<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCreatorSales extends Model
{
    protected $table = 'bingo_creator_sales';
    protected $fillable = [
        'room',
        'creator_user',
        'coin',
        'sale_amount',
        'final_sale_amount',
        'room_commn',
        'seller_commn',
        'rke_commn',
        'prize_pool_amount',
        'created_at',
        'updated_at'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
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

    public static function getValueByRoomCreatorUser($room, $creatorUser, $coin, $value)
    {
        return self::where('room', $room)->where('coin', $coin)
            ->where('creator_user', $creatorUser)->value($value);
    }

    public static function existsByRoomCreatorUser($room, $creatorUser, $coin = '')
    {
        $query = self::where('room', $room)->where('creator_user', $creatorUser);
        if ($coin)  $query->where('coin', $coin);
        return $query->exists();
    }

    public static function updateOrCreateSale($roomID, $creatorUser, $roomCommn, $coin, $amount, $finalAmount)
    {
        $commnRKE = BingoRoomConfig::getValueByRoom($roomID, 'rke_commn') ?? 0;
        $commnRKE =  $amount * ($commnRKE / 100);

        $sellerCommn = $amount - $finalAmount;
        $roomCommn = $amount * ($roomCommn / 100);
        $prizePool = $amount -  $roomCommn - $sellerCommn - $commnRKE;

        $sale = self::firstOrCreate(
            ['room' => $roomID, 'creator_user' => $creatorUser, 'coin' => $coin],
            [
                'sale_amount' => $amount,
                'final_sale_amount' => $finalAmount,
                'room_commn' => $roomCommn,
                'seller_commn' => $sellerCommn,
                'rke_commn' => $commnRKE,
                'prize_pool_amount' => $prizePool
            ]
        );

        if (!$sale->wasRecentlyCreated && $amount) {
            $sale->increment('sale_amount', $amount);
            $sale->increment('final_sale_amount', $finalAmount);
            $sale->increment('room_commn', $roomCommn);
            $sale->increment('seller_commn', $sellerCommn);
            $sale->increment('rke_commn', $commnRKE);
            $sale->increment('prize_pool_amount', $prizePool);
        }
    }
}
