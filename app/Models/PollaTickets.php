<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaTickets extends Model
{
    protected $table = 'polla_tickets';
    protected $fillable = [
        'draw',
        'player_ticket',
        'coin',
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

    public function Player()
    {
        return $this->belongsTo(PollaPlayers::class, 'player_ticket');
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

    public static function getAllByDraw($draw, $fields = ['*'])
    {
        return self::select($fields)->where('draw', $draw)->get();
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
