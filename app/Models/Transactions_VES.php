<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions_VES extends Model
{
    protected $table = "transactions_ves";
    protected $fillable = [
        'id_transk', 'fecha', 'user', 'banco_e', 'monto', 'tipo', 'CommerceExchangeRate',
        'num_ref', 'banco_r', 'capture', 'status', 'motivo'
    ];

    public function User()
    {
        return $this->belongsTo('App\Models\User', 'user');
    }

    public function BancoE()
    {
        return $this->belongsTo('App\Models\Banco', 'banco_e');
    }
    public function BancoR()
    {
        return $this->belongsTo('App\Models\Banco', 'banco_r');
    }
}
