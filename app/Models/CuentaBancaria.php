<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaBancaria extends Model
{
  protected $table = "bank_accounts_chl";
  protected $fillable = [
    'id', 'rut', 'bank', 'num_account', 'type', 'user', 'email'
  ];

  public function Banco()
  {
    return $this->belongsTo('App\Models\Banco_CHL', 'bank');
  }
}
