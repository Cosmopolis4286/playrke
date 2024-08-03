<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banks_CHL extends Model
{
    protected $table = "banks_chl";
    protected $fillable = ['codigo', 'nombre'];

    public function cuentaBancaria()
    {
        return $this->hasMany('App\Models\CuentaBancaria', 'id');
    }
}
