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

    public static function updateOrCreateSale($roomID, $creatorUser, $coin, $amount, $sellerCommn)
    {
        $roomConfig = BingoRoomConfig::getByRoom($roomID, ['rke_commn', 'room_commn']);

        $rkeCommn =  round($amount * ($roomConfig->rke_commn / 100), 3);
        $roomCommn = round($amount * ($roomConfig->room_commn / 100), 3);
        $sellerCommn = round(($sellerCommn ?? 0), 3);
        $prizePool = round(($amount - $rkeCommn - $roomCommn - $sellerCommn), 3);

        $sale = self::firstOrCreate(
            ['room' => $roomID, 'creator_user' => $creatorUser, 'coin' => $coin],
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
