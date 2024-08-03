<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banks_BRA extends Model
{
    protected $table = "banks_bra";
    protected $fillable = ['name', 'code', 'full_name', 'ispb'];

    public static function getByCode($code)
    {
        return self::where('code', $code)->first();
    }
}
