<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaTicketPrice extends Model
{
    protected $table = 'polla_ticket_price';
    protected $fillable = ['draw', 'creator_user', 'coin', 'ticket_price', 'status', 'created_at', 'updated_at'];

    public function Draw()
    {
        return $this->belongsTo(PollaDraw::class, 'draw');
    }

    public function Coin()
    {
        return $this->belongsTo(Coin::class, 'coin');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }

    public static function getValueTicketPrice($draw, $coin, $creatorUser)
    {
        return self::where('draw', $draw)->where('coin', $coin)
            ->where('creator_user', $creatorUser)->value('ticket_price');
    }

    public static function getOnlyCoinsTicketPrice($draw, $creatorUser)
    {
        if (!$creatorUser) return collect();

        return self::where('draw', $draw)->where('creator_user', $creatorUser)->groupBy('coin')
            ->select('coin', PollaTicketPrice::raw('MAX(ticket_price) as ticket_price'))
            ->pluck('ticket_price', 'coin');
    }

    public static function getAllTicketPrice($draw, $creatorUser)
    {
        return self::with('Coin')->where('draw', $draw)->where('creator_user', $creatorUser)->get();
    }

    public static function getTicketPrice($draw, $coin, $creatorUser, $fields = ['id', 'ticket_price', 'status'])
    {
        return self::select($fields)->where('draw', $draw)->where('coin', $coin)
            ->where('creator_user', $creatorUser)->first();
    }
}
