<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions_CLP extends Model
{
    protected $table = "transactions_clp";
    protected $fillable = [
        'user', 'banco_e', 'buyOrder', 'vci', 'type_transk', 'transactionDate',
        'transk_date', 'transk_hour', 'paymentTypeCode', 'authorizationCode',
        'cardNumber', 'num_ref', 'banco_r', 'capture', 'status', 'motivo'
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
