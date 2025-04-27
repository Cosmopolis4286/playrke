<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lottery extends Model
{
    protected $table = 'lotteries';
    protected $fillable = ['name', 'code', 'numbers', 'schedules', 'logo', 'status'];

    public const NUMBERS = [
        '00036' => '00-0-36',
        '199' => '1-99',
        '1999' => '1-999'
    ];

    public const SCHEDULES = [
        '0800AM' => '08:00 AM',
        '0900AM' => '09:00 AM',
        '1000AM' => '10:00 AM',
        '1100AM' => '11:00 AM',
        '1200PM' => '12:00 PM',
        '0100PM' => '01:00 PM',
        '0200PM' => '02:00 PM',
        '0300PM' => '03:00 PM',
        '0400PM' => '04:00 PM',
        '0500PM' => '05:00 PM',
        '0600PM' => '06:00 PM',
        '0700PM' => '07:00 PM'
    ];

    public static function getById($id, $fields = ['*'])
    {
        return self::select($fields)->where('id', $id)->first();
    }

    public static function getValueById($id, $value)
    {
        return self::where('id', $id)->value($value);
    }
}
