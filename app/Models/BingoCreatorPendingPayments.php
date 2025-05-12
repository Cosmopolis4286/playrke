<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoCreatorPendingPayments extends Model
{
    protected $table = 'bingo_creator_pending_payments';
    protected $fillable = [
        'room',
        'creator_user',
        'creator_type',
        'creator_id',
        'creator_commn',
        'ctn_price',
        'status',
        'created_at',
        'updated_at'
    ];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function CreatorType()
    {
        return $this->belongsTo(UserType::class, 'creator_type');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByRoom($room, $fields = ['*'])
    {
        return self::select($fields)->where('room', $room)->first();
    }

    public static function getValueByRoom($room, $value)
    {
        return self::where('room', $room)->value($value);
    }

    public static function getByRoomCreatorUser($room, $creatorUser, $fields = ['*'])
    {
        return self::select($fields)->where('room', $room)->where('creator_user', $creatorUser)->first();
    }

    public static function getByCreatorRoom($creator, $room, $fields = ['*'])
    {
        return self::select($fields)->where('creator_type', $creator['type'])
            ->where('creator_id', $creator['id'])->where('room', $room)->first();
    }

    public static function getValueByCreatorRoom($creator, $room, $value = 'id')
    {
        // Convertir $creatorType si es un string
        if (is_string($creator['type'])) {
            $creatorType = UserType::getValueByPrefix($creator['type'], 'id');
        } else {
            $creatorType = $creator['type'];
        }

        return self::where('creator_type', $creatorType)
            ->where('creator_id', $creator['id'])
            ->where('room', $room)->value($value);
    }

    public static function existsByRoomCreatorUser($room, $creatorUser)
    {
        return self::where('room', $room)->where('creator_user', $creatorUser)->exists();
    }
}
