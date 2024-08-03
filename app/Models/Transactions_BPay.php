<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions_BPay extends Model
{
    protected $table = "transactions_bpay";
    protected $fillable = [
        'id_transk', 'fecha', 'user', 'crypto_mon', 'monto', 'tipo',
        'id_orden', 'num_r', 'capture', 'status', 'motivo'
    ];

    public function User()
    {
        return $this->belongsTo('App\Models\User', 'user');
    }
}
