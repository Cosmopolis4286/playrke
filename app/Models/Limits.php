<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Limits extends Model
{
    protected $table = "limits";
    protected $fillable = ['id', 'name', 'info', 'country'];

    public function User()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function Wallet()
    {
        return $this->belongsTo(Wallet::class, 'id');
    }

    public function RoomOffBingo()
    {
        return $this->belongsTo(BingoOffRoom::class, 'id');
    }

    public function Seller()
    {
        return $this->belongsTo(Seller::class, 'id');
    }
}
