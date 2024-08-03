<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BingoOffRoomCountry extends Model
{
    protected $table = "bingo_off_rooms_country";
    protected $fillable = ['id', 'country', 'commerce', 'status'];

    public function Country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function Commerce()
    {
        return $this->belongsTo(Commerce::class, 'commerce');
    }
}
