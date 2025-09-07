<?php

namespace App\Models\Games\Bingo\Creators;

use Illuminate\Database\Eloquent\Model;

class BingoCreator extends Model
{
    protected $table = 'bingo_creator';
    protected $fillable = [
        'room',
        'creator_user_id',
        'creator_type',
        'creator_id',
        'creator_commn',
        'card_price',
        'allowed_currencies',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $casts = ['allowed_currencies' => 'array'];

    public function Room()
    {
        return $this->belongsTo(BingoRoom::class, 'room');
    }

    public function CreatorType()
    {
        return $this->belongsTo(Role::class, 'creator_type');
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
        return self::select($fields)->where('room', $room)->where('creator_user_id', $creatorUser)->first();
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
            $creatorType = Role::getValueByPrefix($creator['type'], 'id');
        } else {
            $creatorType = $creator['type'];
        }

        return self::where('creator_type', $creatorType)
            ->where('creator_id', $creator['id'])
            ->where('room', $room)->value($value);
    }

    public static function existsByRoomCreatorUser($room, $creatorUser)
    {
        return self::where('room', $room)->where('creator_user_id', $creatorUser)->exists();
    }
}
