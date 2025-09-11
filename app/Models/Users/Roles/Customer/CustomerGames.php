<?php

namespace App\Models\Users\Roles\Customer;

use Illuminate\Database\Eloquent\Model;

class CustomerGames extends Model
{
    protected $table = "customers_games";
    protected $fillable = ['customer', 'game', 'customer_game_id', 'product', 'description', 'status'];

    public function Customer()
    {
        return $this->belongsTo(Customer::class, 'customer');
    }

    public function Game()
    {
        return $this->belongsTo(Game::class, 'game');
    }

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getByGameID($customer, $game, $fields = ['*'])
    {
        return self::select($fields)->where('customer', $customer)->where('game', $game)->first();
    }

    public static function getValueByCustomerGameID($customer, $game, $value = 'customer_game_id')
    {
        return self::where('customer', $customer)->where('game', $game)->value($value);
    }
}
