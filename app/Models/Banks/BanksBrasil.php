<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BanksBrasil extends Model
{
    protected $table = "banks_bra";
    protected $fillable = ['name', 'full_name', 'ispb'];

    public static function getByISPB($ispb, $fields = ['*'])
    {
        return self::select($fields)->where('ispb', $ispb)->first();
    }

    public static function getValueByISPB($ispb, $value)
    {
        return self::where('ispb', $ispb)->value($value);
    }

    public static function existsISPB($ispb)
    {
        return self::where('ispb', $ispb)->exists();
    }
}
