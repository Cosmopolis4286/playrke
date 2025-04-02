<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollaTicketPriceAutomatic extends Model
{
    protected $table = 'automatic_polla_ticket_price';
    protected $fillable = ['automatic_polla_id', 'coin', 'ticket_price', 'status', 'created_at', 'updated_at'];

    public function AutomaticBingoRoom()
    {
        return $this->belongsTo(PollaDrawAutomatic::class, 'automatic_polla_id');
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

    public static function getValueTicketPrice($coin, $automaticPollaID)
    {
        return self::where('automatic_polla_id', $automaticPollaID)->where('coin', $coin)->value('ticket_price');
    }

    public static function getOnlyCoinsTicketPrice($automaticPollaID)
    {
        if (!$automaticPollaID) return collect();

        return self::where('automatic_polla_id', $automaticPollaID)->groupBy('coin')
            ->select('coin', PollaTicketPrice::raw('MAX(ticket_price) as ticket_price'))
            ->pluck('ticket_price', 'coin');
    }

    public static function getAllTicketPrice($automaticPollaID)
    {
        return self::with('Coin')->where('automatic_polla_id', $automaticPollaID)->get();
    }

    public static function getTicketPrice($coin, $automaticPollaID, $fields = ['id', 'ticket_price', 'status'])
    {
        return self::select($fields)->where('coin', $coin)->where('automatic_polla_id', $automaticPollaID)->first();
    }
}
