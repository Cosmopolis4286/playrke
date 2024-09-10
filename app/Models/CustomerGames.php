<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGames extends Model
{
    protected $table = "customers_games";
    protected $fillable = ['customer', 'game', 'customer_game_id', 'status'];

    public function Customer()
    {
        return $this->belongsTo(Commerce::class, 'customer');
    }

    public function Game()
    {
        return $this->belongsTo(Games::class, 'game');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::where('id', $id)->select($fields)->first();
    }

    public static function getCustomerGameID($customer, $game)
    {
        return self::where('customer', $customer)->where('game', $game)->value('customer_game_id');
    }
}
