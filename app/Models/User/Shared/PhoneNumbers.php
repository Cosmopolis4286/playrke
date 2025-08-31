<?php

namespace App\Models\User\Shared;

use App\Models\Countries\Country;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class PhoneNumbers extends Model
{
    protected $table = "phone_numbers";
    protected $fillable = ['user', 'country', 'phone_number', 'status'];

    public function User()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public static function existsPhoneNumber($phoneNumber)
    {
        return self::where('phone_number', $phoneNumber)->exists();
    }
}
