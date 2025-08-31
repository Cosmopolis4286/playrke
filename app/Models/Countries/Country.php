<?php

namespace App\Models\Countries;

use App\Models\User\Roles\Player\External\ExternalPlayers;
use App\Models\User\Roles\Seller\Seller;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = "countries";

    protected $fillable = [
        'name',
        'iso_code_2',
        'iso_code_3',
        'status'
    ];

    /**
     * Relación: un país tiene muchos vendedores.
     */
    public function sellers()
    {
        return $this->hasMany(Seller::class, 'country_id');
    }

    /**
     * Relación: un país tiene muchos jugadores externos.
     */
    public function externalPlayers()
    {
        return $this->hasMany(ExternalPlayers::class, 'country_id');
    }
}
