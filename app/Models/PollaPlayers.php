<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaPlayers extends Model
{
    protected $table = 'polla_players';
    protected $fillable = [
        'draw',
        'player_ticket',
        'coin',
        'exchange_rate',
        'amount',
        'n1',
        'n2',
        'n3',
        'n4',
        'n5',
        'n6',
        'n7',
        'status'
    ];

    public function Draw()
    {
        return $this->belongsTo(PollaDraw::class, 'draw');
    }

    public function PlayerTicket()
    {
        return $this->belongsTo(UserType::class, 'creator_type');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getByDraw($draw, $fields = ['*'])
    {
        return self::select($fields)->where('draw', $draw)->first();
    }

    public static function getValueByDraw($draw, $value)
    {
        return self::where('draw', $draw)->value($value);
    }

    public static function getByCreatorDraw($creator, $draw, $fields = ['*'])
    {
        return self::select($fields)->where($creator['type'], $creator['id'])->where('draw', $draw)->first();
    }

    public static function getValueByCreatorDraw($creator, $draw, $value)
    {
        return self::where($creator['type'], $creator['id'])->where('draw', $draw)->value($value);
    }
}
