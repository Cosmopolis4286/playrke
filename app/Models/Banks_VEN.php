<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banks_VEN extends Model
{
    protected $table = "banks_ven";

    protected $fillable = ['name', 'code', 'full_name'];

    public function PagoMovil()
    {
        return $this->hasMany(PagoMovil::class, 'bank');
    }
}
